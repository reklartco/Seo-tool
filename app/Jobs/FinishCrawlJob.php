<?php

namespace App\Jobs;

use App\Models\Crawl;
use App\Models\Issue;
use App\Models\Page;
use App\Models\PageLink;
use App\Notifications\CrawlFinished;
use App\Seo\Crawler\HttpFetcher;
use App\Seo\HealthScore;
use App\Seo\SiteContext;
use App\Seo\SiteRuleRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Runs once every page job in the batch has settled: site wide rules,
 * link graph counters, health score and the diff against the previous crawl.
 */
class FinishCrawlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public const EXTERNAL_LINK_SAMPLE = 150;

    public function __construct(public int $crawlId) {}

    public function handle(SiteRuleRunner $runner, HttpFetcher $fetcher): void
    {
        $crawl = Crawl::with('project.team')->find($this->crawlId);

        if (! $crawl || $crawl->status === 'done') {
            return;
        }

        $project = $crawl->project;

        $this->refreshLinkCounters($crawl);
        $this->markSitemapMembership($crawl);

        $pages = Page::where('project_id', $project->id)
            ->where('last_crawl_id', $crawl->id)
            ->get();

        $context = new SiteContext(
            project: $project,
            crawl: $crawl,
            pages: $pages,
            robots: $crawl->robots['robots'] ?? ['status' => 0, 'body' => '', 'blocks_all' => false, 'sitemaps' => []],
            sitemap: $crawl->robots['sitemap'] ?? ['found' => false, 'valid' => true, 'urls' => []],
            brokenLinks: $this->brokenLinks($crawl, $pages, $fetcher),
        );

        foreach (array_chunk($runner->run($context), 200) as $chunk) {
            Issue::insert(array_map(fn ($issue) => [
                'project_id' => $project->id,
                'crawl_id' => $crawl->id,
                'page_id' => $issue->pageId,
                'rule_key' => $issue->ruleKey,
                'severity' => $issue->severity,
                'category' => $issue->category,
                'message' => $issue->message,
                'details' => json_encode($issue->details, JSON_UNESCAPED_UNICODE),
                'status' => 'open',
                'created_at' => now(),
                'updated_at' => now(),
            ], $chunk));
        }

        $this->closeStaleIssues($crawl);

        $counts = Issue::where('crawl_id', $crawl->id)
            ->selectRaw('severity, count(*) as total')
            ->groupBy('severity')
            ->pluck('total', 'severity');

        $pageCount = max(1, $pages->count());

        $score = HealthScore::calculate(
            (int) $counts->get('critical', 0),
            (int) $counts->get('warning', 0),
            (int) $counts->get('notice', 0),
            $pageCount,
        );

        $previous = Crawl::where('project_id', $project->id)
            ->where('id', '<', $crawl->id)
            ->where('status', 'done')
            ->latest('id')
            ->first();

        $crawl->update([
            'status' => 'done',
            'finished_at' => now(),
            'health_score' => $score,
            'issues_count' => (int) $counts->sum(),
            'new_issues' => $this->countNew($crawl, $previous),
            'resolved_issues' => $previous ? max(0, $previous->issues_count - (int) $counts->sum()) : 0,
        ]);

        $project->update([
            'health_score' => $score,
            'health_score_delta' => $previous?->health_score ? $score - $previous->health_score : 0,
            'last_crawled_at' => now(),
        ]);

        $project->team->owner?->notify(new CrawlFinished($crawl->fresh()));
    }

    /** Recount incoming internal links per page from the link graph. */
    private function refreshLinkCounters(Crawl $crawl): void
    {
        Page::where('project_id', $crawl->project_id)->update(['internal_links_in' => 0]);

        $counts = PageLink::where('crawl_id', $crawl->id)
            ->where('is_internal', true)
            ->selectRaw('to_url_hash, count(*) as total')
            ->groupBy('to_url_hash')
            ->pluck('total', 'to_url_hash');

        foreach ($counts as $hash => $total) {
            Page::where('project_id', $crawl->project_id)
                ->where('url_hash', $hash)
                ->update(['internal_links_in' => $total]);
        }
    }

    private function markSitemapMembership(Crawl $crawl): void
    {
        $urls = $crawl->robots['sitemap']['urls'] ?? [];

        Page::where('project_id', $crawl->project_id)->update(['in_sitemap' => false]);

        foreach (array_chunk($urls, 500) as $chunk) {
            Page::where('project_id', $crawl->project_id)
                ->whereIn('url_hash', array_map(fn ($url) => Page::hashUrl($url), $chunk))
                ->update(['in_sitemap' => true]);
        }
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @return list<array{to_url: string, status: int|null, from_page_id: int, is_internal: bool}>
     */
    private function brokenLinks(Crawl $crawl, $pages, HttpFetcher $fetcher): array
    {
        $broken = [];
        $statuses = $pages->pluck('status_code', 'url');

        $internal = PageLink::where('crawl_id', $crawl->id)->where('is_internal', true)->get();

        foreach ($internal as $link) {
            $status = $statuses->get($link->to_url);

            if ($status !== null && $status >= 400) {
                $broken[] = [
                    'to_url' => $link->to_url,
                    'status' => (int) $status,
                    'from_page_id' => $link->from_page_id,
                    'is_internal' => true,
                ];
            }
        }

        $external = PageLink::where('crawl_id', $crawl->id)
            ->where('is_internal', false)
            ->get()
            ->unique('to_url')
            ->take(self::EXTERNAL_LINK_SAMPLE);

        foreach ($external as $link) {
            $result = $fetcher->head($link->to_url, 8);
            $status = $result->statusCode;

            if ($status === 405 || $status === 0) {
                continue; // HEAD not allowed or network noise: do not accuse the link.
            }

            if ($status >= 400) {
                $broken[] = [
                    'to_url' => $link->to_url,
                    'status' => $status,
                    'from_page_id' => $link->from_page_id,
                    'is_internal' => false,
                ];
            }
        }

        return $broken;
    }

    /** Issues from earlier crawls that this crawl no longer reports are resolved. */
    private function closeStaleIssues(Crawl $crawl): void
    {
        Issue::where('project_id', $crawl->project_id)
            ->where('status', 'open')
            ->where(function ($query) use ($crawl) {
                $query->whereNull('crawl_id')->orWhere('crawl_id', '!=', $crawl->id);
            })
            ->update(['status' => 'fixed', 'resolved_by' => 'crawl', 'fixed_at' => now()]);
    }

    private function countNew(Crawl $crawl, ?Crawl $previous): int
    {
        if (! $previous) {
            return (int) Issue::where('crawl_id', $crawl->id)->count();
        }

        $previousKeys = Issue::where('crawl_id', $previous->id)
            ->get(['rule_key', 'page_id'])
            ->map(fn ($issue) => $issue->rule_key.'#'.($issue->page_id ?? 0))
            ->flip();

        return Issue::where('crawl_id', $crawl->id)
            ->get(['rule_key', 'page_id'])
            ->reject(fn ($issue) => $previousKeys->has($issue->rule_key.'#'.($issue->page_id ?? 0)))
            ->count();
    }
}

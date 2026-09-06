<?php

namespace App\Jobs;

use App\Models\Crawl;
use App\Models\Issue;
use App\Models\Page;
use App\Models\PageLink;
use App\Models\PageSnapshot;
use App\Seo\Crawler\HttpFetcher;
use App\Seo\Crawler\PageParser;
use App\Seo\Crawler\RobotsAndSitemap;
use App\Seo\Crawler\UrlNormalizer;
use App\Seo\PageContext;
use App\Seo\RuleRunner;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Fetches one URL, stores the page, runs the page level rules and queues
 * newly discovered internal links back into the same batch.
 */
class CrawlPageJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public int $tries = 2;

    public const MAX_DEPTH = 5;

    public function __construct(
        public int $crawlId,
        public string $url,
        public int $depth = 0,
    ) {}

    public function handle(HttpFetcher $fetcher, PageParser $parser, RuleRunner $runner): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $crawl = Crawl::with('project')->find($this->crawlId);

        if (! $crawl || $crawl->status === 'failed') {
            return;
        }

        $project = $crawl->project;
        $url = UrlNormalizer::normalize($this->url);

        if ($url === null || ! UrlNormalizer::sameSite($url, $project->domain)) {
            return;
        }

        $hash = Page::hashUrl($url);

        // Another job in this batch may already have taken this URL.
        $alreadyCrawled = Page::where('project_id', $project->id)
            ->where('url_hash', $hash)
            ->where('last_crawl_id', $crawl->id)
            ->exists();

        if ($alreadyCrawled) {
            return;
        }

        $result = $fetcher->get($url);
        $parsed = $result->isHtml() && $result->body !== '' ? $parser->parse($result->body, $url) : [];

        $page = Page::updateOrCreate(
            ['project_id' => $project->id, 'url_hash' => $hash],
            [
                'url' => $url,
                'final_url' => $result->finalUrl !== $url ? $result->finalUrl : null,
                'status_code' => $result->statusCode,
                'redirect_hops' => $result->redirectHops,
                'content_type' => $result->contentType(),
                'title' => $parsed['title'] ?? null,
                'meta_description' => $parsed['meta_description'] ?? null,
                'h1' => $parsed['h1'] ?? null,
                'canonical' => $parsed['canonical'] ?? null,
                'robots_meta' => $parsed['robots_meta'] ?? null,
                'word_count' => $parsed['word_count'] ?? 0,
                'html_size' => $parsed['html_size'] ?? $result->size(),
                'content_hash' => $parsed['content_hash'] ?? null,
                'load_time_ms' => $result->loadTimeMs,
                'depth' => $this->depth,
                'internal_links_out' => 0,
                'indexable' => ! str_contains(mb_strtolower($parsed['robots_meta'] ?? ''), 'noindex'),
                'first_seen_at' => now(),
                'last_crawled_at' => now(),
                'last_crawl_id' => $crawl->id,
            ],
        );

        PageSnapshot::create([
            'page_id' => $page->id,
            'crawl_id' => $crawl->id,
            'title' => $page->title,
            'meta_description' => $page->meta_description,
            'h1' => $page->h1,
            'canonical' => $page->canonical,
            'robots_meta' => $page->robots_meta,
            'status_code' => $page->status_code,
            'content_hash' => $page->content_hash,
        ]);

        $context = new PageContext(
            url: $url,
            html: '',
            statusCode: $result->statusCode,
            meta: $parsed,
            wordCount: $parsed['word_count'] ?? 0,
            loadTimeMs: $result->loadTimeMs,
            depth: $this->depth,
            projectKeywords: $project->keywords()->pluck('keyword')->all(),
            redirectHops: $result->redirectHops,
            domain: $project->domain,
            cms: $project->cms,
            contentType: $result->contentType(),
            error: $result->error,
        );

        $issues = $runner->run($context);

        Issue::where('crawl_id', $crawl->id)->where('page_id', $page->id)->delete();

        foreach (array_chunk($issues, 200) as $chunk) {
            Issue::insert(array_map(fn ($issue) => [
                'project_id' => $project->id,
                'crawl_id' => $crawl->id,
                'page_id' => $page->id,
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

        $page->update(['issues_count' => count($issues)]);

        $this->storeLinks($crawl, $project->domain, $page, $context);
        $this->queueDiscoveredLinks($crawl, $context);

        DB::table('crawls')->where('id', $crawl->id)->increment('pages_crawled');
        $project->team->currentUsage()->increment('crawled_pages');
    }

    private function storeLinks(Crawl $crawl, string $domain, Page $page, PageContext $context): void
    {
        PageLink::where('crawl_id', $crawl->id)->where('from_page_id', $page->id)->delete();

        $rows = [];
        $internal = 0;

        foreach ($context->links() as $link) {
            $target = UrlNormalizer::normalize($link['url']);

            if ($target === null) {
                continue;
            }

            $isInternal = UrlNormalizer::sameSite($target, $domain);
            $internal += $isInternal ? 1 : 0;

            $rows[] = [
                'crawl_id' => $crawl->id,
                'project_id' => $crawl->project_id,
                'from_page_id' => $page->id,
                'to_url' => $target,
                'to_url_hash' => Page::hashUrl($target),
                'anchor' => mb_substr($link['anchor'], 0, 500),
                'is_internal' => $isInternal,
                'nofollow' => $link['nofollow'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            PageLink::insert($chunk);
        }

        $page->update(['internal_links_out' => $internal]);
    }

    private function queueDiscoveredLinks(Crawl $crawl, PageContext $context): void
    {
        if ($this->depth >= self::MAX_DEPTH || ! $this->batch()) {
            return;
        }

        $robots = $crawl->robots['robots']['disallow'] ?? [];
        $budget = $crawl->project->max_pages;

        $known = Page::where('project_id', $crawl->project_id)->pluck('url_hash')->flip();
        $queued = 0;
        $jobs = [];

        foreach ($context->internalLinks() as $link) {
            if ($crawl->pages_total + $queued >= $budget) {
                break;
            }

            $target = UrlNormalizer::normalize($link['url']);

            if ($target === null || ! RobotsAndSitemap::isAllowed($target, $robots)) {
                continue;
            }

            $hash = Page::hashUrl($target);

            if ($known->has($hash)) {
                continue;
            }

            $known->put($hash, true);
            $jobs[] = new self($crawl->id, $target, $this->depth + 1);
            $queued++;
        }

        if ($jobs === []) {
            return;
        }

        DB::table('crawls')->where('id', $crawl->id)->increment('pages_total', count($jobs));
        $this->batch()->add($jobs);
    }
}

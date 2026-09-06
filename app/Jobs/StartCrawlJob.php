<?php

namespace App\Jobs;

use App\Models\Crawl;
use App\Models\Project;
use App\Seo\Crawler\HttpFetcher;
use App\Seo\Crawler\RobotsAndSitemap;
use App\Seo\Crawler\UrlNormalizer;
use App\Services\PlanLimits;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Throwable;

/**
 * Opens a crawl: reads robots.txt and the sitemap, then queues the first
 * batch of page jobs. Discovered links enqueue further jobs into the batch.
 */
class StartCrawlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(public Crawl $crawl) {}

    public function handle(HttpFetcher $fetcher): void
    {
        $crawl = $this->crawl->fresh();
        $project = $crawl->project;

        $crawl->update(['status' => 'running', 'started_at' => now()]);

        $reader = new RobotsAndSitemap($fetcher);
        $origin = $project->url();

        $robots = $reader->robots($origin);
        $sitemap = $reader->sitemap($origin, $robots['sitemaps'], $project->max_pages);

        $crawl->update(['robots' => ['robots' => $robots, 'sitemap' => [
            'found' => $sitemap['found'],
            'valid' => $sitemap['valid'],
            'count' => count($sitemap['urls']),
            'urls' => array_slice($sitemap['urls'], 0, 5000),
        ]]]);

        $seeds = collect([UrlNormalizer::normalize($origin)])
            ->merge($sitemap['urls'])
            ->filter()
            ->filter(fn (string $url) => UrlNormalizer::sameSite($url, $project->domain))
            ->filter(fn (string $url) => RobotsAndSitemap::isAllowed($url, $robots['disallow']))
            ->unique()
            ->take($this->pageBudget($project))
            ->values();

        $crawl->update(['pages_total' => max(1, $seeds->count())]);

        $jobs = $seeds->map(fn (string $url) => new CrawlPageJob($crawl->id, $url, 0))->all();

        Bus::batch($jobs)
            ->name("crawl:{$crawl->id}")
            ->allowFailures()
            ->finally(function (Batch $batch) use ($crawl) {
                FinishCrawlJob::dispatch($crawl->id);
            })
            ->onQueue('crawl')
            ->dispatch();
    }

    private function pageBudget(Project $project): int
    {
        $remaining = PlanLimits::for($project->team)->remaining('max_monthly_crawl_pages');

        return max(1, min($project->max_pages, $remaining));
    }

    public function failed(Throwable $e): void
    {
        $this->crawl->update([
            'status' => 'failed',
            'error' => $e->getMessage(),
            'finished_at' => now(),
        ]);
    }
}

<?php

namespace App\Actions;

use App\Jobs\StartCrawlJob;
use App\Models\Crawl;
use App\Models\Project;
use App\Services\PlanLimits;
use RuntimeException;

class StartCrawl
{
    /**
     * Queue a crawl for the project, refusing when one is already running
     * or the team has spent its monthly page budget.
     */
    public function __invoke(Project $project): Crawl
    {
        if ($project->crawls()->whereIn('status', ['queued', 'running'])->exists()) {
            throw new RuntimeException('Bu proje için zaten süren bir tarama var.');
        }

        if (! PlanLimits::for($project->team)->allows('max_monthly_crawl_pages')) {
            throw new RuntimeException('Aylık tarama sayfası limitin doldu. Planını yükseltebilirsin.');
        }

        $crawl = $project->crawls()->create([
            'status' => 'queued',
            'pages_total' => 0,
            'pages_crawled' => 0,
        ]);

        StartCrawlJob::dispatch($crawl)->onQueue('crawl');

        return $crawl;
    }
}

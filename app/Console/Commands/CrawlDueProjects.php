<?php

namespace App\Console\Commands;

use App\Actions\StartCrawl;
use App\Models\Project;
use Illuminate\Console\Command;
use RuntimeException;

class CrawlDueProjects extends Command
{
    protected $signature = 'seo:crawl-due';

    protected $description = 'Projelerin tarama sıklığına göre bekleyen taramaları kuyruğa alır';

    public function handle(StartCrawl $starter): int
    {
        $started = 0;

        Project::query()
            ->whereIn('crawl_frequency', ['daily', 'weekly'])
            ->with('team')
            ->chunkById(100, function ($projects) use ($starter, &$started) {
                foreach ($projects as $project) {
                    if (! $this->isDue($project)) {
                        continue;
                    }

                    try {
                        $starter($project);
                        $started++;
                    } catch (RuntimeException $e) {
                        $this->warn("{$project->domain}: {$e->getMessage()}");
                    }
                }
            });

        $this->info("{$started} tarama kuyruğa alındı.");

        return self::SUCCESS;
    }

    private function isDue(Project $project): bool
    {
        if (! $project->last_crawled_at) {
            return true;
        }

        return match ($project->crawl_frequency) {
            'daily' => $project->last_crawled_at->lt(now()->subDay()),
            'weekly' => $project->last_crawled_at->lt(now()->subWeek()),
            default => false,
        };
    }
}

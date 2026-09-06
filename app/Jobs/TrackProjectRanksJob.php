<?php

namespace App\Jobs;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Nightly fan-out: one FetchSerpJob per tracked keyword of a project.
 */
class TrackProjectRanksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $projectId) {}

    public function handle(): void
    {
        $project = Project::with('team')->find($this->projectId);

        if (! $project) {
            return;
        }

        $project->keywords()
            ->orderBy('id')
            ->chunkById(200, function ($keywords) {
                foreach ($keywords as $keyword) {
                    FetchSerpJob::dispatch($keyword->id)->onQueue('serp');
                }
            });
    }
}

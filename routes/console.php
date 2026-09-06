<?php

use App\Jobs\SyncSearchConsoleJob;
use App\Jobs\TrackProjectRanksJob;
use App\Models\Project;
use Illuminate\Support\Facades\Schedule;

// Nightly rank check for every project (spec §5).
Schedule::call(function () {
    Project::query()->select('id')->chunkById(100, function ($projects) {
        foreach ($projects as $project) {
            TrackProjectRanksJob::dispatch($project->id)->onQueue('serp');
        }
    });
})->dailyAt('03:00')->name('serp-tracking')->withoutOverlapping();

// Search Console lags 2-3 days, so pull a rolling window every morning.
Schedule::call(function () {
    Project::query()->whereNotNull('gsc_connected_at')->select('id')->chunkById(100, function ($projects) {
        foreach ($projects as $project) {
            SyncSearchConsoleJob::dispatch($project->id)->onQueue('default');
        }
    });
})->dailyAt('05:00')->name('gsc-sync')->withoutOverlapping();

// Scheduled crawls per project cadence.
Schedule::command('seo:crawl-due')->hourly()->name('due-crawls')->withoutOverlapping();

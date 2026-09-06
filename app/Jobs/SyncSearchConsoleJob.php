<?php

namespace App\Jobs;

use App\Models\GscDaily;
use App\Models\GscQuery;
use App\Models\IntegrationLog;
use App\Models\Project;
use App\Services\Google\SearchConsoleClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Pulls daily totals and query rows. Search Console lags two to three days,
 * so a rolling window is re-fetched on every run; the first sync walks back
 * 16 months.
 */
class SyncSearchConsoleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public int $projectId, public bool $initial = false) {}

    public function handle(SearchConsoleClient $client): void
    {
        $project = Project::with('gscToken')->find($this->projectId);
        $token = $project?->gscToken;

        if (! $project || ! $token || blank($token->property)) {
            return;
        }

        $end = now()->subDays(2)->toDateString();
        $start = $this->initial
            ? now()->subMonths(16)->toDateString()
            : now()->subDays(10)->toDateString();

        try {
            foreach ($client->query($token, $token->property, $start, $end, ['date'], 5000) as $row) {
                GscDaily::updateOrCreate(
                    ['project_id' => $project->id, 'date' => $row['keys'][0]],
                    [
                        'clicks' => (int) ($row['clicks'] ?? 0),
                        'impressions' => (int) ($row['impressions'] ?? 0),
                        'ctr' => round((float) ($row['ctr'] ?? 0), 4),
                        'position' => round((float) ($row['position'] ?? 0), 2),
                    ],
                );
            }

            // Query rows are heavy: keep a shorter window for the detail table.
            $queryStart = $this->initial ? now()->subDays(90)->toDateString() : $start;

            foreach ($client->query($token, $token->property, $queryStart, $end, ['query', 'page'], 5000) as $row) {
                GscQuery::updateOrCreate(
                    [
                        'project_id' => $project->id,
                        'date' => $end,
                        'query' => mb_substr($row['keys'][0] ?? '', 0, 255),
                        'page' => $row['keys'][1] ?? null,
                    ],
                    [
                        'clicks' => (int) ($row['clicks'] ?? 0),
                        'impressions' => (int) ($row['impressions'] ?? 0),
                        'ctr' => round((float) ($row['ctr'] ?? 0), 4),
                        'position' => round((float) ($row['position'] ?? 0), 2),
                    ],
                );
            }

            IntegrationLog::create([
                'project_id' => $project->id,
                'source' => 'gsc',
                'action' => 'sync',
                'payload' => ['start' => $start, 'end' => $end, 'initial' => $this->initial],
            ]);
        } catch (Throwable $e) {
            IntegrationLog::create([
                'project_id' => $project->id,
                'source' => 'gsc',
                'action' => 'sync',
                'payload' => ['error' => $e->getMessage()],
                'success' => false,
            ]);

            throw $e;
        }
    }
}

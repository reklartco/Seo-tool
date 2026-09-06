<?php

namespace App\Jobs;

use App\Models\IntegrationLog;
use App\Models\Keyword;
use App\Models\RankHistory;
use App\Models\SerpTop10;
use App\Notifications\RankChanged;
use App\Seo\Rank\Location;
use App\Seo\Rank\RankProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * One keyword, one SERP lookup: updates the rank, the history row and the
 * top ten competitors, then notifies on a meaningful move.
 */
class FetchSerpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    /** A move of this many positions or more is worth an alert (spec §5). */
    public const ALERT_THRESHOLD = 3;

    public function __construct(public int $keywordId) {}

    public function handle(RankProvider $provider): void
    {
        $keyword = Keyword::with('project.team.owner')->find($this->keywordId);

        if (! $keyword) {
            return;
        }

        $project = $keyword->project;

        $result = $provider->fetchRank(
            $keyword->keyword,
            $project->domain,
            Location::forProject($project),
        );

        $previous = $keyword->current_rank;

        RankHistory::updateOrCreate(
            ['keyword_id' => $keyword->id, 'checked_at' => now()->toDateString()],
            ['rank' => $result->rank, 'serp_url' => $result->url, 'serp_features' => $result->features],
        );

        SerpTop10::where('keyword_id', $keyword->id)->where('checked_at', now()->toDateString())->delete();

        foreach ($result->top as $row) {
            SerpTop10::create([
                'keyword_id' => $keyword->id,
                'checked_at' => now()->toDateString(),
                'position' => $row['position'],
                'domain' => $row['domain'],
                'url' => $row['url'],
                'title' => $row['title'],
            ]);
        }

        $keyword->update([
            'previous_rank' => $previous,
            'current_rank' => $result->rank,
            'start_rank' => $keyword->start_rank ?? $result->rank,
            'best_rank' => $result->rank === null
                ? $keyword->best_rank
                : min($result->rank, $keyword->best_rank ?? $result->rank),
            // Positive delta means the keyword climbed towards #1.
            'rank_delta' => $result->rank !== null && $previous !== null ? $previous - $result->rank : 0,
            'serp_url' => $result->url,
            'last_checked_at' => now(),
        ]);

        $project->team->currentUsage()->increment('serp_queries');

        IntegrationLog::create([
            'project_id' => $project->id,
            'source' => 'dataforseo',
            'action' => 'serp',
            'payload' => ['keyword' => $keyword->keyword, 'rank' => $result->rank],
        ]);

        $delta = $keyword->fresh()->rank_delta;

        if ($previous !== null && abs($delta) >= self::ALERT_THRESHOLD) {
            $project->team->owner?->notify(new RankChanged($keyword->fresh(), $previous));
        }
    }

    public function failed(Throwable $e): void
    {
        $keyword = Keyword::find($this->keywordId);

        if ($keyword) {
            IntegrationLog::create([
                'project_id' => $keyword->project_id,
                'source' => 'dataforseo',
                'action' => 'serp',
                'payload' => ['keyword' => $keyword->keyword, 'error' => $e->getMessage()],
                'success' => false,
            ]);
        }
    }
}

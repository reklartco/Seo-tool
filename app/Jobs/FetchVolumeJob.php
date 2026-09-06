<?php

namespace App\Jobs;

use App\Models\Keyword;
use App\Models\Project;
use App\Seo\Rank\Location;
use App\Seo\Rank\RankProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fills in search volume, CPC and competition right after keywords are added.
 */
class FetchVolumeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    /**
     * @param  list<int>  $keywordIds
     */
    public function __construct(public int $projectId, public array $keywordIds = []) {}

    public function handle(RankProvider $provider): void
    {
        $project = Project::find($this->projectId);

        if (! $project) {
            return;
        }

        $keywords = Keyword::where('project_id', $project->id)
            ->when($this->keywordIds !== [], fn ($q) => $q->whereIn('id', $this->keywordIds))
            ->when($this->keywordIds === [], fn ($q) => $q->whereNull('search_volume'))
            ->get();

        if ($keywords->isEmpty()) {
            return;
        }

        $volumes = $provider->fetchVolume(
            $keywords->pluck('keyword')->all(),
            Location::forProject($project),
        );

        foreach ($keywords as $keyword) {
            $data = $volumes[mb_strtolower($keyword->keyword)] ?? null;

            if ($data === null) {
                continue;
            }

            $keyword->update([
                'search_volume' => $data['search_volume'],
                'cpc' => $data['cpc'],
                'competition' => $data['competition'],
            ]);
        }
    }
}

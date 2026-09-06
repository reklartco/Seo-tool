<?php

use App\Jobs\FetchSerpJob;
use App\Jobs\FetchVolumeJob;
use App\Livewire\Keywords\Index;
use App\Models\Keyword;
use App\Models\Plan;
use App\Models\Project;
use App\Models\RankHistory;
use App\Models\SerpTop10;
use App\Models\Team;
use App\Models\User;
use App\Notifications\RankChanged;
use App\Seo\Rank\DataForSeoProvider;
use App\Seo\Rank\Location;
use App\Seo\Rank\RankProvider;
use App\Seo\Rank\SerpResult;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

function projectWithPlan(array $plan = []): Project
{
    $user = User::factory()->create();
    $planModel = Plan::factory()->create($plan);
    $team = Team::factory()->create(['owner_id' => $user->id, 'plan_id' => $planModel->id]);
    $team->users()->attach($user, ['role' => 'owner']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return Project::factory()->create(['team_id' => $team->id, 'domain' => 'ornek.com']);
}

it('reads the rank and the top ten out of a dataforseo response', function () {
    Http::fake([
        '*/serp/google/organic/live/advanced' => Http::response([
            'tasks' => [[
                'status_code' => 20000,
                'result' => [[
                    'items' => [
                        ['type' => 'featured_snippet'],
                        ['type' => 'organic', 'rank_absolute' => 1, 'url' => 'https://rakip.com/a', 'title' => 'Rakip'],
                        ['type' => 'organic', 'rank_absolute' => 2, 'url' => 'https://www.ornek.com/etiket', 'title' => 'Biz'],
                    ],
                ]],
            ]],
        ]),
    ]);

    $provider = new DataForSeoProvider(app(Factory::class), 'login', 'sifre');
    $result = $provider->fetchRank('etiket baskı', 'ornek.com', new Location);

    expect($result->rank)->toBe(2)
        ->and($result->url)->toBe('https://www.ornek.com/etiket')
        ->and($result->top)->toHaveCount(2)
        ->and($result->features)->toBe(['featured_snippet']);
});

it('reports no rank when the domain is not in the serp', function () {
    Http::fake([
        '*/serp/*' => Http::response(['tasks' => [[
            'status_code' => 20000,
            'result' => [['items' => [['type' => 'organic', 'rank_absolute' => 1, 'url' => 'https://rakip.com/a']]]],
        ]]]),
    ]);

    $provider = new DataForSeoProvider(app(Factory::class), 'login', 'sifre');

    expect($provider->fetchRank('etiket', 'ornek.com', new Location)->rank)->toBeNull();
});

it('stores rank history and competitors after a serp fetch', function () {
    Notification::fake();

    $project = projectWithPlan();
    $keyword = $project->keywords()->create(['keyword' => 'etiket baskı', 'current_rank' => 14, 'start_rank' => 14]);

    app()->instance(RankProvider::class, new class implements RankProvider
    {
        public function fetchRank(string $keyword, string $domain, Location $location): SerpResult
        {
            return new SerpResult(
                keyword: $keyword,
                rank: 7,
                url: 'https://ornek.com/etiket',
                top: [['position' => 1, 'domain' => 'rakip.com', 'url' => 'https://rakip.com/a', 'title' => 'Rakip']],
            );
        }

        public function fetchVolume(array $keywords, Location $location): array
        {
            return [];
        }
    });

    app()->call([new FetchSerpJob($keyword->id), 'handle']);

    $keyword->refresh();

    expect($keyword->current_rank)->toBe(7)
        ->and($keyword->previous_rank)->toBe(14)
        ->and($keyword->rank_delta)->toBe(7)
        ->and($keyword->best_rank)->toBe(7)
        ->and(RankHistory::where('keyword_id', $keyword->id)->value('rank'))->toBe(7)
        ->and(SerpTop10::where('keyword_id', $keyword->id)->count())->toBe(1);

    Notification::assertSentTo($project->team->owner, RankChanged::class);
});

it('does not alert on a move smaller than three positions', function () {
    Notification::fake();

    $project = projectWithPlan();
    $keyword = $project->keywords()->create(['keyword' => 'etiket', 'current_rank' => 8]);

    app()->instance(RankProvider::class, new class implements RankProvider
    {
        public function fetchRank(string $keyword, string $domain, Location $location): SerpResult
        {
            return new SerpResult(keyword: $keyword, rank: 7);
        }

        public function fetchVolume(array $keywords, Location $location): array
        {
            return [];
        }
    });

    app()->call([new FetchSerpJob($keyword->id), 'handle']);

    Notification::assertNothingSent();
});

it('fills in search volume for new keywords', function () {
    $project = projectWithPlan();
    $keyword = $project->keywords()->create(['keyword' => 'etiket baskı']);

    app()->instance(RankProvider::class, new class implements RankProvider
    {
        public function fetchRank(string $keyword, string $domain, Location $location): SerpResult
        {
            return new SerpResult(keyword: $keyword);
        }

        public function fetchVolume(array $keywords, Location $location): array
        {
            return ['etiket baskı' => ['search_volume' => 1600, 'cpc' => 4.2, 'competition' => 0.45]];
        }
    });

    app()->call([new FetchVolumeJob($project->id, [$keyword->id]), 'handle']);

    expect($keyword->fresh()->search_volume)->toBe(1600)
        ->and((float) $keyword->fresh()->cpc)->toBe(4.2);
});

it('adds keywords in bulk, skipping duplicates', function () {
    Queue::fake();

    $project = projectWithPlan(['max_keywords' => 20]);
    $project->keywords()->create(['keyword' => 'etiket baskı']);

    Livewire::actingAs($project->team->owner)
        ->test(Index::class)
        ->set('bulk', "Etiket Baskı\nsticker baskı\n\nürün etiketi\nsticker baskı")
        ->call('addKeywords')
        ->assertHasNoErrors();

    expect(Keyword::where('project_id', $project->id)->pluck('keyword')->sort()->values()->all())
        ->toBe(['etiket baskı', 'sticker baskı', 'ürün etiketi']);

    Queue::assertPushed(FetchVolumeJob::class);
    Queue::assertPushed(FetchSerpJob::class, 2);
});

it('stops adding keywords at the plan limit', function () {
    Queue::fake();

    $project = projectWithPlan(['max_keywords' => 2]);

    Livewire::actingAs($project->team->owner)
        ->test(Index::class)
        ->set('bulk', "bir\niki\nüç\ndört")
        ->call('addKeywords');

    expect(Keyword::where('project_id', $project->id)->count())->toBe(2);
});

it('removes a keyword from tracking', function () {
    $project = projectWithPlan();
    $keyword = $project->keywords()->create(['keyword' => 'etiket']);

    Livewire::actingAs($project->team->owner)
        ->test(Index::class)
        ->call('deleteKeyword', $keyword->id);

    expect(Keyword::find($keyword->id))->toBeNull();
});

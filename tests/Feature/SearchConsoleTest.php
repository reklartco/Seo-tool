<?php

use App\Jobs\SyncSearchConsoleJob;
use App\Livewire\SearchConsole\Index;
use App\Models\GscDaily;
use App\Models\GscQuery;
use App\Models\GscToken;
use App\Models\Keyword;
use App\Models\Project;
use App\Services\Google\SearchConsoleClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

function connectedProject(): Project
{
    $project = projectWithPlan(['max_keywords' => 50]);

    GscToken::create([
        'project_id' => $project->id,
        'access_token' => 'token',
        'refresh_token' => 'refresh',
        'expires_at' => now()->addHour(),
        'property' => 'sc-domain:ornek.com',
    ]);

    $project->update(['gsc_connected_at' => now()]);

    return $project->fresh('gscToken');
}

it('refreshes an expired access token before querying', function () {
    Http::fake([
        'oauth2.googleapis.com/*' => Http::response(['access_token' => 'yeni', 'expires_in' => 3600]),
        '*/sites' => Http::response(['siteEntry' => [['siteUrl' => 'sc-domain:ornek.com', 'permissionLevel' => 'siteOwner']]]),
    ]);

    $project = connectedProject();
    $project->gscToken->update(['expires_at' => now()->subMinute()]);

    $client = app(SearchConsoleClient::class);
    $sites = $client->sites($project->gscToken->fresh());

    expect($sites)->toBe(['sc-domain:ornek.com'])
        ->and($project->gscToken->fresh()->access_token)->toBe('yeni');
});

it('skips properties the account cannot read', function () {
    Http::fake(['*/sites' => Http::response(['siteEntry' => [
        ['siteUrl' => 'sc-domain:ornek.com', 'permissionLevel' => 'siteOwner'],
        ['siteUrl' => 'sc-domain:baska.com', 'permissionLevel' => 'siteUnverifiedUser'],
    ]])]);

    expect(app(SearchConsoleClient::class)->sites(connectedProject()->gscToken))
        ->toBe(['sc-domain:ornek.com']);
});

it('stores daily totals and query rows from a sync', function () {
    $project = connectedProject();

    Http::fake([
        '*/searchAnalytics/query' => Http::sequence()
            ->push(['rows' => [
                ['keys' => ['2026-09-01'], 'clicks' => 40, 'impressions' => 900, 'ctr' => 0.044, 'position' => 12.4],
                ['keys' => ['2026-09-02'], 'clicks' => 55, 'impressions' => 1010, 'ctr' => 0.054, 'position' => 11.1],
            ]])
            ->push(['rows' => [
                ['keys' => ['etiket baskı', 'https://ornek.com/etiket'], 'clicks' => 12, 'impressions' => 890, 'ctr' => 0.013, 'position' => 11.0],
            ]]),
    ]);

    app()->call([new SyncSearchConsoleJob($project->id), 'handle']);

    expect(GscDaily::where('project_id', $project->id)->count())->toBe(2)
        ->and(GscDaily::whereDate('date', '2026-09-02')->value('clicks'))->toBe(55)
        ->and(GscQuery::where('project_id', $project->id)->value('query'))->toBe('etiket baskı');
});

it('surfaces opportunity keywords and adds them to tracking', function () {
    Queue::fake();

    $project = connectedProject();

    GscQuery::create([
        'project_id' => $project->id,
        'date' => now()->subDay()->toDateString(),
        'query' => 'kare etiket baskı',
        'page' => 'https://ornek.com/kare',
        'clicks' => 12,
        'impressions' => 890,
        'ctr' => 0.013,
        'position' => 11,
    ]);

    Livewire::actingAs($project->team->owner)
        ->test(Index::class)
        ->assertSee('kare etiket baskı')
        ->call('trackQuery', 'kare etiket baskı');

    expect(Keyword::where('project_id', $project->id)->where('keyword', 'kare etiket baskı')->exists())->toBeTrue();
});

it('leaves already tracked queries out of the opportunity list', function () {
    $project = connectedProject();
    $project->keywords()->create(['keyword' => 'kare etiket baskı']);

    GscQuery::create([
        'project_id' => $project->id,
        'date' => now()->subDay()->toDateString(),
        'query' => 'kare etiket baskı',
        'clicks' => 12,
        'impressions' => 890,
        'position' => 11,
    ]);

    $rows = Livewire::actingAs($project->team->owner)->test(Index::class)->viewData('opportunities');

    expect($rows)->toBeEmpty();
});

it('disconnects search console', function () {
    $project = connectedProject();

    Livewire::actingAs($project->team->owner)
        ->test(Index::class)
        ->call('disconnect');

    expect(GscToken::where('project_id', $project->id)->exists())->toBeFalse()
        ->and($project->fresh()->gsc_connected_at)->toBeNull();
});

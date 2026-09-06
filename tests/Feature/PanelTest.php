<?php

use App\Livewire\Projects\Create;
use App\Models\Project;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('redirects guests away from the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('shows the dashboard to a signed in user', function () {
    $this->actingAs(memberOfTeam())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Genel Bakış');
});

it('creates a project through the three step wizard', function () {
    Queue::fake();

    $user = memberOfTeam(['max_keywords' => 10]);

    $component = Livewire::actingAs($user)
        ->test(Create::class)
        ->set('domain', 'https://www.1etiket.com.tr/etiket')
        ->set('name', '1etiket')
        ->call('saveSite')
        ->assertHasNoErrors()
        ->assertSet('step', 2);

    $project = Project::where('team_id', $user->current_team_id)->first();

    expect($project)->domain->toBe('1etiket.com.tr')->language->toBe('tr');

    $component
        ->set('keywords', "etiket baskı\nsticker baskı")
        ->call('saveKeywords')
        ->assertSet('step', 3)
        ->set('startCrawl', false)
        ->call('finish')
        ->assertRedirect(route('dashboard'));

    expect($project->keywords()->pluck('keyword')->all())->toBe(['etiket baskı', 'sticker baskı']);
});

it('rejects a domain that is already tracked by the team', function () {
    $user = memberOfTeam();
    Project::factory()->create(['team_id' => $user->current_team_id, 'domain' => 'ornek.com']);

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('domain', 'ornek.com')
        ->set('name', 'Örnek')
        ->call('saveSite')
        ->assertHasErrors('domain');
});

it('blocks a project once the plan limit is reached', function () {
    $user = memberOfTeam(['max_projects' => 1]);
    Project::factory()->create(['team_id' => $user->current_team_id]);

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('domain', 'ikinci.com')
        ->set('name', 'İkinci')
        ->call('saveSite')
        ->assertHasErrors('domain');

    expect(Project::where('team_id', $user->current_team_id)->count())->toBe(1);
});

it('lists the keywords of the current project', function () {
    $user = memberOfTeam();
    $project = Project::factory()->create(['team_id' => $user->current_team_id]);
    $project->keywords()->create([
        'keyword' => 'etiket baskı',
        'current_rank' => 7,
        'start_rank' => 14,
        'rank_delta' => 7,
        'search_volume' => 1600,
    ]);

    $this->actingAs($user)
        ->get(route('keywords'))
        ->assertOk()
        ->assertSee('etiket baskı')
        ->assertSee('#');
});

it('groups open issues by rule on the issues screen', function () {
    $user = memberOfTeam();
    $project = Project::factory()->create(['team_id' => $user->current_team_id]);

    foreach (range(1, 3) as $i) {
        $project->issues()->create([
            'rule_key' => 'meta_desc_missing',
            'severity' => 'critical',
            'category' => 'meta',
            'message' => 'Sayfada meta description yok.',
            'status' => 'open',
        ]);
    }

    $this->actingAs($user)
        ->get(route('issues'))
        ->assertOk()
        ->assertSee('Meta description eksik')
        ->assertSee('3 sayfa');
});

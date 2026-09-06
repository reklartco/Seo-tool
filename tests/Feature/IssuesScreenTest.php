<?php

use App\Jobs\GenerateFixJob;
use App\Livewire\Issues\Index;
use App\Models\Issue;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('groups open issues and expands a rule into its pages', function () {
    $project = projectWithPlan();
    $first = pageWithIssue($project);
    pageWithIssue($project);

    Livewire::actingAs($project->team->owner)
        ->test(Index::class)
        ->assertSee('Meta description eksik')
        ->assertSee('2 sayfa')
        ->call('toggle', 'meta_desc_missing')
        ->assertSee($first->page->url);
});

it('shows why a rule matters', function () {
    $project = projectWithPlan();
    pageWithIssue($project);

    Livewire::actingAs($project->team->owner)
        ->test(Index::class)
        ->call('explain', 'meta_desc_missing')
        ->assertSee('tıklama oranını doğrudan etkiler', false);
});

it('ignores every open issue of a rule', function () {
    $project = projectWithPlan();
    pageWithIssue($project);

    Livewire::actingAs($project->team->owner)
        ->test(Index::class)
        ->call('ignoreRule', 'meta_desc_missing');

    expect(Issue::where('project_id', $project->id)->where('status', 'ignored')->count())->toBe(1);
});

it('queues ai fixes straight from the issue list', function () {
    Queue::fake();

    $project = projectWithPlan();
    pageWithIssue($project);

    Livewire::actingAs($project->team->owner)
        ->test(Index::class)
        ->call('generateForRule', 'meta_desc_missing');

    Queue::assertPushed(GenerateFixJob::class, 1);
});

it('does not queue ai fixes for a rule the ai cannot write', function () {
    Queue::fake();

    $project = projectWithPlan();
    pageWithIssue($project, 'broken_internal_link');

    Livewire::actingAs($project->team->owner)
        ->test(Index::class)
        ->call('generateForRule', 'broken_internal_link');

    Queue::assertNothingPushed();
});

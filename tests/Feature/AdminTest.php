<?php

use App\Livewire\Admin\Dashboard;
use App\Models\Plan;
use App\Models\Team;
use Livewire\Livewire;

it('keeps ordinary users out of the admin panel', function () {
    $this->actingAs(memberOfTeam())->get(route('admin'))->assertForbidden();
});

it('lets a super admin see teams and usage', function () {
    $admin = memberOfTeam();
    $admin->update(['is_super_admin' => true]);

    $admin->currentTeam->currentUsage()->update(['serp_queries' => 1200, 'ai_tokens' => 50000]);

    $this->actingAs($admin)
        ->get(route('admin'))
        ->assertOk()
        ->assertSee($admin->currentTeam->name)
        // 1200 * 0.002 + 50 * 0.009 = 2.85
        ->assertSee('$2.85');
});

it('assigns a plan to a team', function () {
    $admin = memberOfTeam();
    $admin->update(['is_super_admin' => true]);

    $plan = Plan::factory()->create(['name' => 'Ajans']);

    Livewire::actingAs($admin)
        ->test(Dashboard::class)
        ->call('editPlan', $admin->current_team_id)
        ->set('selectedPlan', $plan->id)
        ->call('assignPlan');

    expect(Team::find($admin->current_team_id)->plan_id)->toBe($plan->id);
});

<?php

use App\Models\Plan;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Unit tests get the application container (service providers, config) but
// never touch the database.
pest()->extend(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A user who owns a team on a plan, with that team selected.
 */
function memberOfTeam(array $planAttributes = []): User
{
    $user = User::factory()->create();
    $plan = Plan::factory()->create($planAttributes);
    $team = Team::factory()->create(['owner_id' => $user->id, 'plan_id' => $plan->id]);
    $team->users()->attach($user, ['role' => 'owner']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return $user->refresh();
}

/**
 * A project on a fresh team, ready for keyword and integration tests.
 */
function projectWithPlan(array $plan = []): Project
{
    $user = memberOfTeam($plan);

    return Project::factory()->create([
        'team_id' => $user->current_team_id,
        'domain' => 'ornek.com',
    ]);
}

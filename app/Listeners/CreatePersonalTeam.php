<?php

namespace App\Listeners;

use App\Models\Plan;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;

class CreatePersonalTeam
{
    /**
     * Give every new user a personal team on the entry level plan.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || $user->current_team_id) {
            return;
        }

        $team = Team::create([
            'owner_id' => $user->id,
            'name' => Str::before($user->name, ' ').' Takımı',
            'slug' => Str::slug(Str::before($user->email, '@')).'-'.Str::lower(Str::random(6)),
            'plan_id' => Plan::where('is_active', true)->orderBy('sort_order')->value('id'),
        ]);

        $team->users()->attach($user, ['role' => 'owner']);

        $user->forceFill(['current_team_id' => $team->id])->save();
    }
}

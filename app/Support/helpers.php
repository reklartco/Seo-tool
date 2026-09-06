<?php

use App\Models\Project;
use App\Models\WpConnection;
use Illuminate\Support\Facades\Crypt;

if (! function_exists('current_project')) {
    /**
     * The project the user is currently looking at: whatever they last
     * switched to, falling back to the first project of the current team.
     *
     * Memoized on the container so a single request resolves it once.
     */
    function current_project(): ?Project
    {
        return app()->has('seotool.current_project')
            ? app('seotool.current_project')
            : tap(resolve_current_project(), fn (?Project $project) => app()->instance('seotool.current_project', $project));
    }
}

if (! function_exists('resolve_current_project')) {
    function resolve_current_project(): ?Project
    {
        $team = auth()->user()?->currentTeam;

        if (! $team) {
            return null;
        }

        $id = session('current_project_id');

        return ($id ? $team->projects()->find($id) : null)
            ?? $team->projects()->orderBy('id')->first();
    }
}

if (! function_exists('rule_label')) {
    /**
     * Short label for a rule key, falling back to the rule's own message.
     */
    function rule_label(string $ruleKey, ?string $fallback = null): string
    {
        return config("seo.rule_labels.{$ruleKey}") ?? $fallback ?? $ruleKey;
    }
}

if (! function_exists('decrypt_wp_key')) {
    /**
     * The plugin API key is stored hashed for verification and encrypted for
     * outbound calls; this returns the usable secret.
     */
    function decrypt_wp_key(WpConnection $connection): string
    {
        return Crypt::decryptString($connection->api_key_hash);
    }
}

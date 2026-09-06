<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes sure an authenticated user always has a current team resolved,
 * falling back to the first team they belong to.
 */
class EnsureTeamContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->current_team_id) {
            $team = $user->teams()->first();

            if ($team) {
                $user->forceFill(['current_team_id' => $team->id])->save();
            }
        }

        return $next($request);
    }
}

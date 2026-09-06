<?php

namespace App\Http\Controllers;

use App\Jobs\SyncSearchConsoleJob;
use App\Models\GscToken;
use App\Models\Project;
use App\Services\Google\SearchConsoleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class SearchConsoleController extends Controller
{
    /** Kick off the OAuth dance for the current project. */
    public function connect(Request $request, Project $project, SearchConsoleClient $client)
    {
        abort_unless($project->team_id === $request->user()->current_team_id, 403);

        if (! $client->configured()) {
            return redirect()->route('integrations')
                ->with('error', 'Google istemci bilgileri .env içinde tanımlı değil.');
        }

        $state = Str::random(32);
        $request->session()->put('gsc_state', $state);

        return redirect()->away($client->authorizationUrl($project->id, $state));
    }

    public function callback(Request $request, SearchConsoleClient $client)
    {
        [$projectId, $state] = array_pad(explode(':', (string) $request->query('state'), 2), 2, null);

        if (! $state || $state !== $request->session()->pull('gsc_state')) {
            return redirect()->route('integrations')->with('error', 'Yetkilendirme doğrulanamadı, tekrar dene.');
        }

        $project = Project::where('team_id', $request->user()->current_team_id)->find($projectId);

        if (! $project || ! $request->query('code')) {
            return redirect()->route('integrations')->with('error', 'Yetkilendirme tamamlanamadı.');
        }

        try {
            $tokens = $client->exchangeCode($request->query('code'));
        } catch (Throwable $e) {
            return redirect()->route('integrations')->with('error', $e->getMessage());
        }

        $token = GscToken::updateOrCreate(
            ['project_id' => $project->id],
            [
                'access_token' => $tokens['access_token'] ?? null,
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 3600) - 60),
            ],
        );

        // Pre-select the property when exactly one of the account's sites matches.
        try {
            $sites = $client->sites($token);
            $guess = $client->guessProperty($project);

            $match = collect($sites)->first(fn (string $site) => $site === $guess || str_contains($site, $project->domain));

            if ($match) {
                $token->update(['property' => $match]);
                $project->update(['gsc_connected_at' => now()]);
                SyncSearchConsoleJob::dispatch($project->id, true);
            }
        } catch (Throwable $e) {
            return redirect()->route('search-console')->with('error', $e->getMessage());
        }

        return redirect()->route('search-console')->with('status', 'Search Console bağlandı.');
    }
}

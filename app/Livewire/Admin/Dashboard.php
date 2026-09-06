<?php

namespace App\Livewire\Admin;

use App\Models\Crawl;
use App\Models\Fix;
use App\Models\Keyword;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Team;
use App\Models\TeamUsage;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Super admin overview: who is on which plan, what they are burning, and
 * roughly what that costs us in external API calls.
 */
#[Layout('layouts.app')]
#[Title('Admin')]
class Dashboard extends Component
{
    /** Rough unit costs used for the monthly estimate (spec §5). */
    public const SERP_COST_USD = 0.002;

    public const AI_COST_PER_1K_TOKENS_USD = 0.009;

    public ?int $editingTeam = null;

    public ?int $selectedPlan = null;

    public function editPlan(int $teamId): void
    {
        $this->editingTeam = $teamId;
        $this->selectedPlan = Team::find($teamId)?->plan_id;
    }

    public function assignPlan(): void
    {
        $team = Team::find($this->editingTeam);

        if (! $team || ! $this->selectedPlan) {
            return;
        }

        $team->update(['plan_id' => $this->selectedPlan]);

        $this->editingTeam = null;
        $this->dispatch('toast', message: 'Plan güncellendi.', type: 'success');
    }

    public function teams(): Collection
    {
        return Team::with('plan', 'owner')
            ->withCount('projects')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(function (Team $team) {
                $usage = TeamUsage::where('team_id', $team->id)
                    ->where('period', now()->format('Y-m'))
                    ->first();

                return [
                    'model' => $team,
                    'keywords' => Keyword::whereIn('project_id', $team->projects()->select('id'))->count(),
                    'crawled_pages' => (int) ($usage->crawled_pages ?? 0),
                    'serp_queries' => (int) ($usage->serp_queries ?? 0),
                    'ai_generations' => (int) ($usage->ai_generations ?? 0),
                    'ai_tokens' => (int) ($usage->ai_tokens ?? 0),
                ];
            });
    }

    public function render()
    {
        $usage = TeamUsage::where('period', now()->format('Y-m'));

        $serpQueries = (int) (clone $usage)->sum('serp_queries');
        $aiTokens = (int) (clone $usage)->sum('ai_tokens');

        return view('livewire.admin.dashboard', [
            'teams' => $this->teams(),
            'plans' => Plan::orderBy('sort_order')->get(),
            'totals' => [
                'teams' => Team::count(),
                'users' => User::count(),
                'projects' => Project::count(),
                'keywords' => Keyword::count(),
                'crawls_today' => Crawl::whereDate('created_at', today())->count(),
                'fixes_applied' => Fix::where('status', 'applied')->count(),
                'crawled_pages' => (int) (clone $usage)->sum('crawled_pages'),
                'serp_queries' => $serpQueries,
                'ai_tokens' => $aiTokens,
                'cost_usd' => round($serpQueries * self::SERP_COST_USD + $aiTokens / 1000 * self::AI_COST_PER_1K_TOKENS_USD, 2),
            ],
        ]);
    }
}

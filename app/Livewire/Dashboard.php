<?php

namespace App\Livewire;

use App\Models\Issue;
use App\Models\Keyword;
use App\Models\Project;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Genel Bakış')]
class Dashboard extends Component
{
    public ?Project $project = null;

    public function mount(): void
    {
        $this->project = current_project();
    }

    /**
     * Rule level roll-up of the open issues, worst first.
     */
    public function topIssues(): Collection
    {
        if (! $this->project) {
            return collect();
        }

        return Issue::query()
            ->where('project_id', $this->project->id)
            ->open()
            ->selectRaw('rule_key, severity, category, min(message) as message, count(*) as pages')
            ->groupBy('rule_key', 'severity', 'category')
            ->orderByRaw("case severity when 'critical' then 0 when 'warning' then 1 else 2 end")
            ->orderByDesc('pages')
            ->limit(6)
            ->get();
    }

    public function movers(): Collection
    {
        if (! $this->project) {
            return collect();
        }

        return Keyword::query()
            ->where('project_id', $this->project->id)
            ->whereNotNull('current_rank')
            ->where('rank_delta', '!=', 0)
            ->orderByRaw('abs(rank_delta) desc')
            ->limit(5)
            ->get();
    }

    /**
     * @return array<string, int|string>
     */
    public function stats(): array
    {
        if (! $this->project) {
            return [];
        }

        $keywords = Keyword::where('project_id', $this->project->id);

        return [
            'health' => $this->project->health_score ?? 0,
            'health_delta' => $this->project->health_score_delta,
            'issues' => (clone $this->project->issues()->getQuery())->where('status', 'open')->count(),
            'critical' => (clone $this->project->issues()->getQuery())->where('status', 'open')->where('severity', 'critical')->count(),
            'keywords' => (clone $keywords)->count(),
            'keywords_top10' => (clone $keywords)->whereNotNull('current_rank')->where('current_rank', '<=', 10)->count(),
            'gsc_clicks' => (int) $this->project->gscDaily()->where('date', '>=', now()->subDays(28))->sum('clicks'),
        ];
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'stats' => $this->stats(),
            'topIssues' => $this->topIssues(),
            'movers' => $this->movers(),
        ]);
    }
}

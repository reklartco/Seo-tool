<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithCurrentProject;
use App\Models\GscDaily;
use App\Models\GscQuery;
use App\Models\Issue;
use App\Models\Keyword;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Genel Bakış')]
class Dashboard extends Component
{
    use WithCurrentProject;

    public function mount(): void
    {
        $this->mountCurrentProject();
    }

    /**
     * @return array<string, int|float>
     */
    public function stats(): array
    {
        if (! $this->project) {
            return [];
        }

        $issues = Issue::where('project_id', $this->project->id)->where('status', 'open');
        $keywords = Keyword::where('project_id', $this->project->id);

        $clicks = GscDaily::where('project_id', $this->project->id);
        $current = (int) (clone $clicks)->where('date', '>=', now()->subDays(28)->toDateString())->sum('clicks');
        $previous = (int) (clone $clicks)
            ->whereBetween('date', [now()->subDays(56)->toDateString(), now()->subDays(29)->toDateString()])
            ->sum('clicks');

        $lastCrawl = $this->project->crawls()->where('status', 'done')->latest('id')->first();

        return [
            'health' => $this->project->health_score ?? 0,
            'health_delta' => $this->project->health_score_delta,
            'issues' => (clone $issues)->count(),
            'critical' => (clone $issues)->where('severity', 'critical')->count(),
            'issues_delta' => $lastCrawl ? $lastCrawl->new_issues - $lastCrawl->resolved_issues : 0,
            'keywords' => (clone $keywords)->count(),
            'keywords_top10' => (clone $keywords)->whereNotNull('current_rank')->where('current_rank', '<=', 10)->count(),
            'gsc_clicks' => $current,
            'gsc_delta' => $previous > 0 ? (int) round(($current - $previous) / $previous * 100) : 0,
        ];
    }

    public function topIssues(): Collection
    {
        if (! $this->project) {
            return collect();
        }

        return Issue::query()
            ->where('project_id', $this->project->id)
            ->where('status', 'open')
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
     * Search Console queries sitting on page two: the cheapest wins.
     */
    public function opportunities(): Collection
    {
        if (! $this->project?->gsc_connected_at) {
            return collect();
        }

        $tracked = $this->project->keywords()->pluck('keyword')->map(fn ($k) => mb_strtolower($k))->flip();

        return GscQuery::where('project_id', $this->project->id)
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->whereBetween('position', [8, 20])
            ->orderByDesc('impressions')
            ->limit(30)
            ->get()
            ->unique('query')
            ->reject(fn ($row) => $tracked->has(mb_strtolower($row->query)))
            ->take(4)
            ->values();
    }

    public function render()
    {
        $daily = $this->project
            ? GscDaily::where('project_id', $this->project->id)
                ->where('date', '>=', now()->subDays(28)->toDateString())
                ->orderBy('date')
                ->get()
            : collect();

        return view('livewire.dashboard', [
            'stats' => $this->stats(),
            'topIssues' => $this->topIssues(),
            'movers' => $this->movers(),
            'opportunities' => $this->opportunities(),
            'daily' => $daily,
        ]);
    }
}

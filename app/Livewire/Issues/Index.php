<?php

namespace App\Livewire\Issues;

use App\Models\Issue;
use App\Models\Project;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Hatalar')]
class Index extends Component
{
    public ?Project $project = null;

    #[Url]
    public string $severity = 'all';

    #[Url]
    public string $category = 'all';

    public ?string $expanded = null;

    public function mount(): void
    {
        $this->project = current_project();
    }

    public function toggle(string $ruleKey): void
    {
        $this->expanded = $this->expanded === $ruleKey ? null : $ruleKey;
    }

    public function groups(): Collection
    {
        if (! $this->project) {
            return collect();
        }

        return Issue::query()
            ->where('project_id', $this->project->id)
            ->open()
            ->when($this->severity !== 'all', fn ($q) => $q->where('severity', $this->severity))
            ->when($this->category !== 'all', fn ($q) => $q->where('category', $this->category))
            ->selectRaw('rule_key, severity, category, min(message) as message, count(*) as pages')
            ->groupBy('rule_key', 'severity', 'category')
            ->orderByRaw("case severity when 'critical' then 0 when 'warning' then 1 else 2 end")
            ->orderByDesc('pages')
            ->get();
    }

    public function pagesFor(string $ruleKey): Collection
    {
        if (! $this->project) {
            return collect();
        }

        return Issue::query()
            ->with('page')
            ->where('project_id', $this->project->id)
            ->where('rule_key', $ruleKey)
            ->open()
            ->limit(50)
            ->get();
    }

    public function render()
    {
        return view('livewire.issues.index', [
            'groups' => $this->groups(),
            'expandedPages' => $this->expanded ? $this->pagesFor($this->expanded) : collect(),
        ]);
    }
}

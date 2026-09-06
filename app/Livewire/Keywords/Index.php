<?php

namespace App\Livewire\Keywords;

use App\Models\Keyword;
use App\Models\Project;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Anahtar Kelimeler')]
class Index extends Component
{
    public ?Project $project = null;

    #[Url]
    public string $view = 'grid'; // grid|table

    #[Url]
    public string $filter = 'all'; // all|up|down|top10

    #[Url]
    public string $tag = '';

    public function mount(): void
    {
        $this->project = current_project();
    }

    public function keywords(): Collection
    {
        if (! $this->project) {
            return collect();
        }

        return Keyword::query()
            ->where('project_id', $this->project->id)
            ->when($this->tag !== '', fn ($q) => $q->where('tag', $this->tag))
            ->when($this->filter === 'up', fn ($q) => $q->where('rank_delta', '>', 0))
            ->when($this->filter === 'down', fn ($q) => $q->where('rank_delta', '<', 0))
            ->when($this->filter === 'top10', fn ($q) => $q->whereNotNull('current_rank')->where('current_rank', '<=', 10))
            ->orderByRaw('current_rank is null, current_rank asc')
            ->get();
    }

    /**
     * @return array<string, int|float>
     */
    public function summary(): array
    {
        if (! $this->project) {
            return ['tracked' => 0, 'top3' => 0, 'top10' => 0, 'top100' => 0, 'average' => 0];
        }

        $base = Keyword::where('project_id', $this->project->id);

        $ranked = (clone $base)->whereNotNull('current_rank');

        return [
            'tracked' => (clone $base)->count(),
            'top3' => (clone $ranked)->where('current_rank', '<=', 3)->count(),
            'top10' => (clone $ranked)->where('current_rank', '<=', 10)->count(),
            'top100' => (clone $ranked)->count(),
            'average' => round((float) (clone $ranked)->avg('current_rank'), 1),
        ];
    }

    public function render()
    {
        return view('livewire.keywords.index', [
            'keywords' => $this->keywords(),
            'summary' => $this->summary(),
            'tags' => $this->project
                ? Keyword::where('project_id', $this->project->id)->whereNotNull('tag')->distinct()->orderBy('tag')->pluck('tag')
                : collect(),
        ]);
    }
}

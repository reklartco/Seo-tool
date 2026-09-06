<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\WithCurrentProject;
use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Sayfalar')]
class Index extends Component
{
    use WithCurrentProject, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = 'all'; // all|ok|redirect|error|noindex

    #[Url]
    public string $sort = 'issues_count';

    public string $direction = 'desc';

    public ?int $selected = null;

    public function mount(): void
    {
        $this->mountCurrentProject();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        $this->direction = $this->sort === $column && $this->direction === 'desc' ? 'asc' : 'desc';
        $this->sort = $column;
    }

    public function select(int $pageId): void
    {
        $this->selected = $this->selected === $pageId ? null : $pageId;
    }

    public function render()
    {
        $allowedSorts = ['url', 'status_code', 'word_count', 'internal_links_in', 'issues_count', 'last_crawled_at'];
        $sort = in_array($this->sort, $allowedSorts, true) ? $this->sort : 'issues_count';

        $pages = $this->project
            ? Page::where('project_id', $this->project->id)
                ->when($this->search !== '', fn ($q) => $q->where('url', 'like', '%'.$this->search.'%'))
                ->when($this->status === 'ok', fn ($q) => $q->whereBetween('status_code', [200, 299]))
                ->when($this->status === 'redirect', fn ($q) => $q->whereBetween('status_code', [300, 399]))
                ->when($this->status === 'error', fn ($q) => $q->where('status_code', '>=', 400))
                ->when($this->status === 'noindex', fn ($q) => $q->where('indexable', false))
                ->orderBy($sort, $this->direction)
                ->paginate(25)
            : null;

        return view('livewire.pages.index', [
            'pages' => $pages,
            'detail' => $this->selected
                ? Page::with(['issues' => fn ($q) => $q->where('status', 'open')])->find($this->selected)
                : null,
        ]);
    }
}

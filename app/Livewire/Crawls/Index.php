<?php

namespace App\Livewire\Crawls;

use App\Livewire\Concerns\WithCurrentProject;
use App\Models\Crawl;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tarama Geçmişi')]
class Index extends Component
{
    use WithCurrentProject;

    public function mount(): void
    {
        $this->mountCurrentProject();
    }

    /** Poll while a crawl is in flight so the progress bar advances. */
    public function pollInterval(): ?string
    {
        return $this->project?->crawls()->whereIn('status', ['queued', 'running'])->exists() ? '3s' : null;
    }

    public function render()
    {
        return view('livewire.crawls.index', [
            'crawls' => $this->project
                ? Crawl::where('project_id', $this->project->id)->latest('id')->limit(30)->get()
                : collect(),
            'poll' => $this->pollInterval(),
        ]);
    }
}

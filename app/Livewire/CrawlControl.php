<?php

namespace App\Livewire;

use App\Actions\StartCrawl;
use App\Models\Crawl;
use App\Models\Project;
use Livewire\Component;
use RuntimeException;

/**
 * The "Tara" button in the top bar. While a crawl runs it turns into a live
 * progress pill and polls until the batch finishes.
 */
class CrawlControl extends Component
{
    public ?Project $project = null;

    public function mount(): void
    {
        $this->project = current_project();
    }

    public function start(StartCrawl $starter): void
    {
        if (! $this->project) {
            return;
        }

        try {
            $starter($this->project);
            $this->dispatch('toast', message: 'Tarama kuyruğa alındı.', type: 'success');
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function activeCrawl(): ?Crawl
    {
        return $this->project
            ? $this->project->crawls()->whereIn('status', ['queued', 'running'])->latest('id')->first()
            : null;
    }

    public function render()
    {
        return view('livewire.crawl-control', ['crawl' => $this->activeCrawl()]);
    }
}

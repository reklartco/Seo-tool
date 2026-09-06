<?php

namespace App\Livewire\Concerns;

use App\Actions\StartCrawl;
use App\Models\Project;
use RuntimeException;

trait WithCurrentProject
{
    public ?Project $project = null;

    public function mountCurrentProject(): void
    {
        $this->project = current_project();
    }

    public function startCrawl(StartCrawl $starter): void
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
}

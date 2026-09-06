<?php

namespace App\Livewire\Projects;

use App\Livewire\Concerns\WithCurrentProject;
use App\Services\PlanLimits;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Proje Ayarları')]
class Settings extends Component
{
    use WithCurrentProject;

    public string $name = '';

    public string $protocol = 'https';

    public string $language = 'tr';

    public string $country = 'TR';

    public string $cms = 'custom';

    public string $crawl_frequency = 'weekly';

    public int $max_pages = 500;

    public bool $auto_apply_fixes = false;

    public int $daily_fix_limit = 0;

    public function mount(): void
    {
        $this->mountCurrentProject();

        if (! $this->project) {
            return;
        }

        $this->fill($this->project->only([
            'name', 'protocol', 'language', 'country', 'cms',
            'crawl_frequency', 'max_pages', 'auto_apply_fixes', 'daily_fix_limit',
        ]));
    }

    public function save(): void
    {
        if (! $this->project) {
            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'protocol' => ['required', 'in:http,https'],
            'language' => ['required', 'in:tr,en,de'],
            'country' => ['required', 'in:TR,DE,US,GB'],
            'cms' => ['required', 'in:wordpress,woocommerce,custom'],
            'crawl_frequency' => ['required', 'in:daily,weekly,manual'],
            'max_pages' => ['required', 'integer', 'min:10', 'max:100000'],
            'auto_apply_fixes' => ['boolean'],
            'daily_fix_limit' => ['integer', 'min:0', 'max:1000'],
        ]);

        $limit = PlanLimits::for($this->project->team)->limit('daily_fix_page_limit');

        if ($validated['auto_apply_fixes'] && $limit === 0) {
            $this->addError('auto_apply_fixes', 'Planın otomatik düzeltmeyi kapsamıyor.');

            return;
        }

        $validated['daily_fix_limit'] = min($validated['daily_fix_limit'], $limit ?: $validated['daily_fix_limit']);

        $this->project->update($validated);

        $this->dispatch('toast', message: 'Ayarlar kaydedildi.', type: 'success');
    }

    public function deleteProject()
    {
        $this->project?->delete();
        session()->forget('current_project_id');

        return $this->redirectRoute('dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.projects.settings', [
            'planFixLimit' => $this->project ? PlanLimits::for($this->project->team)->limit('daily_fix_page_limit') : 0,
        ]);
    }
}

<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Services\PlanLimits;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Yeni Proje')]
class Create extends Component
{
    public string $name = '';

    public string $domain = '';

    public string $protocol = 'https';

    public string $language = 'tr';

    public string $country = 'TR';

    public string $cms = 'wordpress';

    public string $crawl_frequency = 'weekly';

    public int $max_pages = 500;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9.-]+\.[a-z]{2,}$/i'],
            'protocol' => ['required', 'in:http,https'],
            'language' => ['required', 'in:tr,en,de'],
            'country' => ['required', 'in:TR,DE,US,GB'],
            'cms' => ['required', 'in:wordpress,woocommerce,custom'],
            'crawl_frequency' => ['required', 'in:daily,weekly,manual'],
            'max_pages' => ['required', 'integer', 'min:10', 'max:100000'],
        ];
    }

    protected array $messages = [
        'domain.regex' => 'Alan adını protokolsüz yaz: örnek.com',
    ];

    public function updatedDomain(string $value): void
    {
        $this->domain = $this->normalizeDomain($value);

        if ($this->name === '') {
            $this->name = $this->domain;
        }
    }

    public function save()
    {
        $team = auth()->user()->currentTeam;

        abort_unless($team, 403);

        $this->domain = $this->normalizeDomain($this->domain);

        $validated = $this->validate();

        if (! PlanLimits::for($team)->allows('max_projects')) {
            throw ValidationException::withMessages([
                'domain' => 'Plan limitine ulaştın. Daha fazla proje için planını yükselt.',
            ]);
        }

        if ($team->projects()->where('domain', $this->domain)->exists()) {
            throw ValidationException::withMessages([
                'domain' => 'Bu alan adı bu takımda zaten kayıtlı.',
            ]);
        }

        $project = Project::create([...$validated, 'team_id' => $team->id]);

        session(['current_project_id' => $project->id]);

        return $this->redirectRoute('dashboard', navigate: true);
    }

    private function normalizeDomain(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = preg_replace('#^https?://#', '', $value) ?? $value;
        $value = preg_replace('#^www\.#', '', $value) ?? $value;

        return rtrim(explode('/', $value)[0], '.');
    }

    public function render()
    {
        return view('livewire.projects.create');
    }
}

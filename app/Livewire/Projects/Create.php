<?php

namespace App\Livewire\Projects;

use App\Actions\StartCrawl;
use App\Jobs\FetchSerpJob;
use App\Jobs\FetchVolumeJob;
use App\Models\Project;
use App\Services\PlanLimits;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

/**
 * Three step wizard (spec §8, screen 8): site, keywords, integrations.
 */
#[Layout('layouts.app')]
#[Title('Yeni Proje')]
class Create extends Component
{
    public int $step = 1;

    public string $name = '';

    public string $domain = '';

    public string $protocol = 'https';

    public string $language = 'tr';

    public string $country = 'TR';

    public string $cms = 'wordpress';

    public string $crawl_frequency = 'weekly';

    public int $max_pages = 500;

    public string $keywords = '';

    public bool $startCrawl = true;

    public ?int $projectId = null;

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

    /** Step 1 → create the project so later steps have something to attach to. */
    public function saveSite(): void
    {
        $team = auth()->user()->currentTeam;

        abort_unless($team, 403);

        $this->domain = $this->normalizeDomain($this->domain);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9.-]+\.[a-z]{2,}$/i'],
            'protocol' => ['required', 'in:http,https'],
            'language' => ['required', 'in:tr,en,de'],
            'country' => ['required', 'in:TR,DE,US,GB'],
            'cms' => ['required', 'in:wordpress,woocommerce,custom'],
            'crawl_frequency' => ['required', 'in:daily,weekly,manual'],
            'max_pages' => ['required', 'integer', 'min:10', 'max:100000'],
        ]);

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

        $this->projectId = $project->id;
        session(['current_project_id' => $project->id]);

        $this->step = 2;
    }

    /** Step 2 → keywords are optional; volumes and ranks are fetched in the background. */
    public function saveKeywords(): void
    {
        $project = $this->project();

        if (! $project) {
            return;
        }

        $remaining = PlanLimits::for($project->team)->remaining('max_keywords');

        $ids = collect(preg_split('/\R/', $this->keywords) ?: [])
            ->map(fn (string $line) => Str::of($line)->trim()->lower()->squish()->value())
            ->filter(fn (string $line) => $line !== '' && mb_strlen($line) <= 255)
            ->unique()
            ->take(max(0, $remaining))
            ->map(fn (string $keyword) => $project->keywords()->create(['keyword' => $keyword])->id)
            ->all();

        if ($ids !== []) {
            FetchVolumeJob::dispatch($project->id, $ids)->onQueue('serp');

            foreach ($ids as $id) {
                FetchSerpJob::dispatch($id)->onQueue('serp');
            }
        }

        $this->step = 3;
    }

    public function finish(StartCrawl $starter)
    {
        $project = $this->project();

        if ($project && $this->startCrawl) {
            try {
                $starter($project);
            } catch (RuntimeException $e) {
                session()->flash('error', $e->getMessage());
            }
        }

        return $this->redirectRoute('dashboard', navigate: true);
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    private function project(): ?Project
    {
        return $this->projectId
            ? Project::where('team_id', auth()->user()->current_team_id)->find($this->projectId)
            : null;
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
        return view('livewire.projects.create', [
            'created' => $this->project(),
        ]);
    }
}

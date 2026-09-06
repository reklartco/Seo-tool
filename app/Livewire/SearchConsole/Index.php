<?php

namespace App\Livewire\SearchConsole;

use App\Jobs\FetchSerpJob;
use App\Jobs\SyncSearchConsoleJob;
use App\Livewire\Concerns\WithCurrentProject;
use App\Models\GscDaily;
use App\Models\GscQuery;
use App\Services\Google\SearchConsoleClient;
use App\Services\PlanLimits;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
#[Title('Search Console')]
class Index extends Component
{
    use WithCurrentProject;

    #[Url]
    public int $days = 28;

    #[Url]
    public string $tab = 'queries'; // queries|pages

    public string $property = '';

    /** @var list<string> */
    public array $availableProperties = [];

    public function mount(): void
    {
        $this->mountCurrentProject();
        $this->property = $this->project?->gscToken?->property ?? '';
    }

    public function loadProperties(SearchConsoleClient $client): void
    {
        $token = $this->project?->gscToken;

        if (! $token) {
            return;
        }

        try {
            $this->availableProperties = $client->sites($token);
        } catch (Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function saveProperty(): void
    {
        $token = $this->project?->gscToken;

        if (! $token || $this->property === '') {
            return;
        }

        $token->update(['property' => $this->property]);
        $this->project->update(['gsc_connected_at' => now()]);

        SyncSearchConsoleJob::dispatch($this->project->id, true);

        $this->dispatch('toast', message: 'Property seçildi, geçmiş veri çekiliyor.', type: 'success');
    }

    public function sync(): void
    {
        if (! $this->project?->gscToken?->property) {
            return;
        }

        SyncSearchConsoleJob::dispatch($this->project->id);
        $this->dispatch('toast', message: 'Search Console eşitlemesi kuyruğa alındı.', type: 'success');
    }

    public function disconnect(): void
    {
        $this->project?->gscToken?->delete();
        $this->project?->update(['gsc_connected_at' => null]);
        $this->property = '';

        $this->dispatch('toast', message: 'Bağlantı kaldırıldı.', type: 'success');
    }

    /** Add a Search Console query to rank tracking in one click. */
    public function trackQuery(string $query): void
    {
        if (! $this->project) {
            return;
        }

        if (! PlanLimits::for($this->project->team)->allows('max_keywords')) {
            $this->dispatch('toast', message: 'Kelime limitin doldu.', type: 'error');

            return;
        }

        $keyword = $this->project->keywords()->firstOrCreate(
            ['keyword' => mb_strtolower($query)],
            ['tag' => 'GSC'],
        );

        FetchSerpJob::dispatch($keyword->id)->onQueue('serp');

        $this->dispatch('toast', message: '"'.$query.'" takibe alındı.', type: 'success');
    }

    public function daily(): Collection
    {
        if (! $this->project) {
            return collect();
        }

        return GscDaily::where('project_id', $this->project->id)
            ->where('date', '>=', now()->subDays($this->days)->toDateString())
            ->orderBy('date')
            ->get();
    }

    /**
     * Rank 8-20 with high impressions and few clicks: the cheapest wins.
     */
    public function opportunities(): Collection
    {
        if (! $this->project) {
            return collect();
        }

        $tracked = $this->project->keywords()->pluck('keyword')->map(fn ($k) => mb_strtolower($k))->flip();

        return GscQuery::where('project_id', $this->project->id)
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->whereBetween('position', [8, 20])
            ->orderByDesc('impressions')
            ->limit(40)
            ->get()
            ->unique('query')
            ->reject(fn ($row) => $tracked->has(mb_strtolower($row->query)))
            ->take(10)
            ->values();
    }

    public function render()
    {
        $daily = $this->daily();

        $rows = $this->project
            ? GscQuery::where('project_id', $this->project->id)
                ->where('date', '>=', now()->subDays($this->days)->toDateString())
                ->orderByDesc('clicks')
                ->limit(200)
                ->get()
            : collect();

        $grouped = $this->tab === 'pages'
            ? $rows->groupBy('page')
            : $rows->groupBy('query');

        return view('livewire.search-console.index', [
            'daily' => $daily,
            'totals' => [
                'clicks' => (int) $daily->sum('clicks'),
                'impressions' => (int) $daily->sum('impressions'),
                'ctr' => $daily->sum('impressions') > 0
                    ? round($daily->sum('clicks') / $daily->sum('impressions') * 100, 2)
                    : 0,
                'position' => round((float) $daily->avg('position'), 1),
            ],
            'rows' => $grouped->map(fn ($group, $key) => [
                'label' => $key,
                'clicks' => (int) $group->sum('clicks'),
                'impressions' => (int) $group->sum('impressions'),
                'position' => round((float) $group->avg('position'), 1),
            ])->sortByDesc('clicks')->take(50)->values(),
            'opportunities' => $this->opportunities(),
        ]);
    }
}

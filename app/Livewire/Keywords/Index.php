<?php

namespace App\Livewire\Keywords;

use App\Jobs\FetchSerpJob;
use App\Jobs\FetchVolumeJob;
use App\Livewire\Concerns\WithCurrentProject;
use App\Models\Keyword;
use App\Models\RankHistory;
use App\Models\SerpTop10;
use App\Services\PlanLimits;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Anahtar Kelimeler')]
class Index extends Component
{
    use WithCurrentProject;

    #[Url]
    public string $view = 'grid'; // grid|table

    #[Url]
    public string $filter = 'all'; // all|up|down|top10|lost

    #[Url]
    public string $tag = '';

    public bool $showAdd = false;

    public string $bulk = '';

    public string $newTag = '';

    public ?int $detailId = null;

    public function mount(): void
    {
        $this->mountCurrentProject();
    }

    public function addKeywords(): void
    {
        if (! $this->project) {
            return;
        }

        $this->validate([
            'bulk' => ['required', 'string', 'max:20000'],
            'newTag' => ['nullable', 'string', 'max:60'],
        ], attributes: ['bulk' => 'anahtar kelimeler']);

        $candidates = collect(preg_split('/\R/', $this->bulk) ?: [])
            ->map(fn (string $line) => Str::of($line)->trim()->lower()->squish()->value())
            ->filter(fn (string $line) => $line !== '' && mb_strlen($line) <= 255)
            ->unique()
            ->values();

        $existing = $this->project->keywords()->pluck('keyword')->map(fn ($k) => mb_strtolower($k))->flip();
        $new = $candidates->reject(fn (string $keyword) => $existing->has($keyword))->values();

        $remaining = PlanLimits::for($this->project->team)->remaining('max_keywords');

        if ($remaining <= 0) {
            $this->dispatch('toast', message: 'Kelime limitin doldu, planını yükseltebilirsin.', type: 'error');

            return;
        }

        $accepted = $new->take($remaining);

        $ids = [];

        foreach ($accepted as $keyword) {
            $ids[] = $this->project->keywords()->create([
                'keyword' => $keyword,
                'tag' => $this->newTag !== '' ? $this->newTag : null,
            ])->id;
        }

        if ($ids !== []) {
            FetchVolumeJob::dispatch($this->project->id, $ids)->onQueue('serp');

            foreach ($ids as $id) {
                FetchSerpJob::dispatch($id)->onQueue('serp');
            }
        }

        $skipped = $new->count() - $accepted->count();

        $this->reset('bulk', 'newTag');
        $this->showAdd = false;

        $this->dispatch(
            'toast',
            message: count($ids).' kelime eklendi'.($skipped > 0 ? ", {$skipped} tanesi limit nedeniyle atlandı" : '').'.',
            type: $skipped > 0 ? 'error' : 'success',
        );
    }

    public function deleteKeyword(int $id): void
    {
        Keyword::where('project_id', $this->project?->id)->whereKey($id)->delete();

        if ($this->detailId === $id) {
            $this->detailId = null;
        }

        $this->dispatch('toast', message: 'Kelime silindi.', type: 'success');
    }

    public function refreshKeyword(int $id): void
    {
        $keyword = Keyword::where('project_id', $this->project?->id)->find($id);

        if (! $keyword) {
            return;
        }

        FetchSerpJob::dispatch($keyword->id)->onQueue('serp');
        $this->dispatch('toast', message: 'Sıra kontrolü kuyruğa alındı.', type: 'success');
    }

    public function showDetail(int $id): void
    {
        $this->detailId = $id;
    }

    public function closeDetail(): void
    {
        $this->detailId = null;
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
            ->when($this->filter === 'lost', fn ($q) => $q->whereNull('current_rank'))
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
        $detail = $this->detailId
            ? Keyword::where('project_id', $this->project?->id)->find($this->detailId)
            : null;

        return view('livewire.keywords.index', [
            'keywords' => $this->keywords(),
            'summary' => $this->summary(),
            'tags' => $this->project
                ? Keyword::where('project_id', $this->project->id)->whereNotNull('tag')->distinct()->orderBy('tag')->pluck('tag')
                : collect(),
            'detail' => $detail,
            'history' => $detail
                ? RankHistory::where('keyword_id', $detail->id)
                    ->where('checked_at', '>=', now()->subDays(90)->toDateString())
                    ->orderBy('checked_at')
                    ->get()
                : collect(),
            'competitors' => $detail
                ? SerpTop10::where('keyword_id', $detail->id)
                    ->orderByDesc('checked_at')
                    ->orderBy('position')
                    ->limit(10)
                    ->get()
                : collect(),
        ]);
    }
}

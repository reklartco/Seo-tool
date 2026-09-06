<?php

namespace App\Livewire\Fixes;

use App\Jobs\ApplyFixJob;
use App\Jobs\GenerateFixJob;
use App\Livewire\Concerns\WithCurrentProject;
use App\Models\Fix;
use App\Models\Issue;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Düzeltmeler')]
class Index extends Component
{
    use WithCurrentProject;

    #[Url]
    public string $tab = 'draft'; // draft|applied|failed

    /** @var array<int, string> */
    public array $edited = [];

    public function mount(): void
    {
        $this->mountCurrentProject();
    }

    /** Queue AI drafts for every fixable open issue of a rule. */
    public function generateForRule(string $ruleKey): void
    {
        if (! $this->project || ! GenerateFixJob::fieldFor($ruleKey)) {
            return;
        }

        $issues = Issue::where('project_id', $this->project->id)
            ->where('rule_key', $ruleKey)
            ->where('status', 'open')
            ->whereNotNull('page_id')
            ->limit(200)
            ->pluck('id');

        foreach ($issues as $id) {
            GenerateFixJob::dispatch($id)->onQueue('ai');
        }

        $this->dispatch('toast', message: $issues->count().' sayfa için AI düzeltmesi kuyruğa alındı.', type: 'success');
    }

    public function approve(int $fixId): void
    {
        $fix = $this->fixQuery()->find($fixId);

        if (! $fix) {
            return;
        }

        if (isset($this->edited[$fixId]) && trim($this->edited[$fixId]) !== '') {
            $fix->update(['new_value' => trim($this->edited[$fixId]), 'generated_by' => 'user']);
        }

        $fix->update(['status' => 'approved']);
        ApplyFixJob::dispatch($fix->id)->onQueue('ai');

        $this->dispatch('toast', message: 'Düzeltme uygulanmak üzere gönderildi.', type: 'success');
    }

    public function approveAll(): void
    {
        $fixes = $this->fixQuery()->where('status', 'draft')->pluck('id');

        foreach ($fixes as $id) {
            $this->approve($id);
        }

        $this->dispatch('toast', message: $fixes->count().' düzeltme onaylandı.', type: 'success');
    }

    public function reject(int $fixId): void
    {
        $this->fixQuery()->where('id', $fixId)->delete();
        $this->dispatch('toast', message: 'Düzeltme reddedildi.', type: 'success');
    }

    public function rollback(int $fixId): void
    {
        $fix = $this->fixQuery()->where('status', 'applied')->find($fixId);

        if (! $fix) {
            return;
        }

        ApplyFixJob::dispatch($fix->id, rollback: true)->onQueue('ai');
        $this->dispatch('toast', message: 'Geri alma kuyruğa alındı.', type: 'success');
    }

    private function fixQuery()
    {
        return Fix::where('project_id', $this->project?->id);
    }

    public function fixes(): Collection
    {
        if (! $this->project) {
            return collect();
        }

        $statuses = match ($this->tab) {
            'applied' => ['applied', 'rolled_back'],
            'failed' => ['failed'],
            default => ['draft', 'approved'],
        };

        return $this->fixQuery()
            ->with('page')
            ->whereIn('status', $statuses)
            ->latest('id')
            ->limit(200)
            ->get();
    }

    /** Rules with open issues the AI can write a replacement for. */
    public function fixableGroups(): Collection
    {
        if (! $this->project) {
            return collect();
        }

        return Issue::where('project_id', $this->project->id)
            ->where('status', 'open')
            ->whereNotNull('page_id')
            ->whereIn('rule_key', array_keys(GenerateFixJob::FIXABLE))
            ->selectRaw('rule_key, count(*) as pages')
            ->groupBy('rule_key')
            ->orderByDesc('pages')
            ->get();
    }

    public function render()
    {
        $fixes = $this->fixes();

        // Seed the editable inputs once so drafts show the suggested value.
        foreach ($fixes as $fix) {
            $this->edited[$fix->id] ??= (string) $fix->new_value;
        }

        return view('livewire.fixes.index', [
            'fixes' => $fixes,
            'groups' => $this->fixableGroups(),
            'appliedToday' => $this->project
                ? $this->fixQuery()->where('status', 'applied')->whereDate('applied_at', today())->count()
                : 0,
        ]);
    }
}

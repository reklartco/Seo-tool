<?php

namespace App\Jobs;

use App\Models\Fix;
use App\Models\Issue;
use App\Models\Project;
use App\Services\Ai\FixGenerator;
use App\Services\PlanLimits;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Turns one issue into a draft fix. Applying it is a separate, explicit step
 * unless the project has auto apply switched on.
 */
class GenerateFixJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    /** Rules the AI can write a replacement for. */
    public const FIXABLE = [
        'title_missing' => 'title',
        'title_too_short' => 'title',
        'title_too_long' => 'title',
        'title_duplicate' => 'title',
        'meta_desc_missing' => 'meta_description',
        'meta_desc_too_short' => 'meta_description',
        'meta_desc_too_long' => 'meta_description',
        'meta_desc_duplicate' => 'meta_description',
        'img_alt_missing' => 'alt',
        'img_alt_empty' => 'alt',
    ];

    public function __construct(public int $issueId) {}

    public static function fieldFor(string $ruleKey): ?string
    {
        return self::FIXABLE[$ruleKey] ?? null;
    }

    public function handle(FixGenerator $generator): void
    {
        $issue = Issue::with(['page', 'project.team'])->find($this->issueId);
        $field = $issue ? self::fieldFor($issue->rule_key) : null;

        if (! $issue || ! $issue->page || ! $field) {
            return;
        }

        $project = $issue->project;
        $limits = PlanLimits::for($project->team);

        if (! $limits->allows('max_monthly_ai_generations')) {
            return;
        }

        try {
            $generated = $generator->generate(
                $project,
                $issue->page,
                $field,
                $project->keywords()->pluck('keyword')->all(),
                $issue->message,
            );
        } catch (Throwable $e) {
            Fix::create([
                'issue_id' => $issue->id,
                'project_id' => $project->id,
                'page_id' => $issue->page_id,
                'field' => $field,
                'old_value' => $this->currentValue($issue, $field),
                'status' => 'failed',
                'error' => $e->getMessage(),
                'model' => config('services.anthropic.model'),
            ]);

            return;
        }

        $value = $generated[$field] ?? null;

        if (! is_string($value) || $value === '') {
            return;
        }

        $fix = Fix::updateOrCreate(
            ['issue_id' => $issue->id, 'field' => $field, 'status' => 'draft'],
            [
                'project_id' => $project->id,
                'page_id' => $issue->page_id,
                'old_value' => $this->currentValue($issue, $field),
                'new_value' => $value,
                'generated_by' => 'ai',
                'model' => config('services.anthropic.model'),
                'prompt_tokens' => $generated['_tokens'] ?? null,
                'wp_object_type' => 'post',
            ],
        );

        $usage = $project->team->currentUsage();
        $usage->increment('ai_generations');
        $usage->increment('ai_tokens', (int) ($generated['_tokens'] ?? 0));

        if ($this->autoApplyAllowed($project)) {
            $fix->update(['status' => 'approved']);
            ApplyFixJob::dispatch($fix->id)->onQueue('ai');
        }
    }

    /**
     * Auto apply is off by default and always bounded by the project's own
     * daily limit as well as the plan's.
     */
    private function autoApplyAllowed(Project $project): bool
    {
        if (! $project->auto_apply_fixes || ! $project->wpConnection) {
            return false;
        }

        $limit = min(
            $project->daily_fix_limit ?: PHP_INT_MAX,
            PlanLimits::for($project->team)->limit('daily_fix_page_limit') ?: 0,
        );

        if ($limit <= 0) {
            return false;
        }

        $appliedToday = Fix::where('project_id', $project->id)
            ->whereIn('status', ['approved', 'applied'])
            ->whereDate('updated_at', today())
            ->count();

        return $appliedToday < $limit;
    }

    private function currentValue(Issue $issue, string $field): ?string
    {
        return match ($field) {
            'title' => $issue->page->title,
            'meta_description' => $issue->page->meta_description,
            'alt' => $issue->details['src'] ?? null,
            default => null,
        };
    }
}

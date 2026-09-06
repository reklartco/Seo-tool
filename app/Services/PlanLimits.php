<?php

namespace App\Services;

use App\Models\Team;

/**
 * Reads plan limits and current period usage for a team.
 *
 * Every limit check funnels through here so jobs, Livewire components and
 * middleware agree on what a team is allowed to do.
 */
class PlanLimits
{
    public function __construct(private readonly Team $team) {}

    public static function for(Team $team): self
    {
        return new self($team);
    }

    public function limit(string $key): int
    {
        return (int) ($this->team->plan?->{$key} ?? 0);
    }

    public function used(string $key): int
    {
        return match ($key) {
            'max_projects' => $this->team->projects()->count(),
            'max_keywords' => $this->team->projects()
                ->join('keywords', 'keywords.project_id', '=', 'projects.id')
                ->count(),
            'max_monthly_crawl_pages' => $this->team->currentUsage()->crawled_pages,
            'max_monthly_ai_generations' => $this->team->currentUsage()->ai_generations,
            default => 0,
        };
    }

    public function remaining(string $key): int
    {
        return max(0, $this->limit($key) - $this->used($key));
    }

    public function allows(string $key, int $amount = 1): bool
    {
        return $this->remaining($key) >= $amount;
    }

    public function percentUsed(string $key): int
    {
        $limit = $this->limit($key);

        if ($limit === 0) {
            return 100;
        }

        return (int) min(100, round($this->used($key) / $limit * 100));
    }
}

<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class KeywordFactory extends Factory
{
    public function definition(): array
    {
        $current = fake()->numberBetween(1, 40);
        $previous = $current + fake()->numberBetween(-8, 8);

        return [
            'project_id' => Project::factory(),
            'keyword' => fake()->unique()->words(2, true),
            'search_volume' => fake()->numberBetween(50, 5000),
            'cpc' => fake()->randomFloat(2, 0.5, 12),
            'competition' => fake()->randomFloat(3, 0, 1),
            'current_rank' => $current,
            'previous_rank' => max(1, $previous),
            'best_rank' => $current,
            'start_rank' => max(1, $previous),
            'rank_delta' => max(1, $previous) - $current,
            'last_checked_at' => now(),
        ];
    }
}

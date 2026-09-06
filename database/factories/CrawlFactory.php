<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class CrawlFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'status' => 'done',
            'pages_total' => 120,
            'pages_crawled' => 120,
            'issues_count' => 42,
            'health_score' => 78,
            'started_at' => now()->subMinutes(10),
            'finished_at' => now(),
        ];
    }
}

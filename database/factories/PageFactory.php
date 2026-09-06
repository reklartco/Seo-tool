<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class PageFactory extends Factory
{
    public function definition(): array
    {
        $url = 'https://'.fake()->domainName().'/'.fake()->slug();

        return [
            'project_id' => Project::factory(),
            'url' => $url,
            'url_hash' => Page::hashUrl($url),
            'status_code' => 200,
            'title' => fake()->sentence(4),
            'meta_description' => fake()->sentence(12),
            'h1' => fake()->sentence(3),
            'word_count' => fake()->numberBetween(120, 1800),
            'depth' => fake()->numberBetween(0, 3),
            'first_seen_at' => now(),
            'last_crawled_at' => now(),
        ];
    }
}

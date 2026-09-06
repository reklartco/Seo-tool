<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlanFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'price_monthly' => 490,
            'max_projects' => 3,
            'max_keywords' => 20,
            'max_monthly_crawl_pages' => 5000,
            'max_auto_fix_sites' => 0,
            'daily_fix_page_limit' => 0,
            'max_monthly_ai_generations' => 50,
        ];
    }
}

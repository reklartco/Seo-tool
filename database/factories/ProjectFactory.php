<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $domain = fake()->unique()->domainName();

        return [
            'team_id' => Team::factory(),
            'name' => $domain,
            'domain' => $domain,
            'protocol' => 'https',
            'language' => 'tr',
            'country' => 'TR',
            'cms' => 'wordpress',
            'crawl_frequency' => 'weekly',
            'max_pages' => 500,
        ];
    }
}

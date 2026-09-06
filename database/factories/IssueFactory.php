<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class IssueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'rule_key' => 'meta_desc_missing',
            'severity' => 'critical',
            'category' => 'meta',
            'message' => 'Sayfada meta description yok.',
            'status' => 'open',
        ];
    }
}

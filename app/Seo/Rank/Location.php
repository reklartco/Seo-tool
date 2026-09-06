<?php

namespace App\Seo\Rank;

use App\Models\Project;

class Location
{
    public function __construct(
        public readonly int $locationCode = 2792, // Turkey
        public readonly string $language = 'tr',
        public readonly string $device = 'desktop',
    ) {}

    public static function forProject(Project $project): self
    {
        return new self(
            locationCode: $project->search_engine_location_code ?: 2792,
            language: $project->language ?: 'tr',
        );
    }
}

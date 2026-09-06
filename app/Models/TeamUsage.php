<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamUsage extends Model
{
    use HasFactory;

    protected $table = 'team_usage';

    protected $guarded = [];

    protected $attributes = [
        'crawled_pages' => 0,
        'serp_queries' => 0,
        'ai_generations' => 0,
        'ai_tokens' => 0,
        'applied_fixes' => 0,
    ];

    protected function casts(): array
    {
        return [
            'crawled_pages' => 'integer',
            'serp_queries' => 'integer',
            'ai_generations' => 'integer',
            'ai_tokens' => 'integer',
            'applied_fixes' => 'integer',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}

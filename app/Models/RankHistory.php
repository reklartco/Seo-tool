<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankHistory extends Model
{
    use HasFactory;

    protected $table = 'rank_history';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'checked_at' => 'date',
            'serp_features' => 'array',
        ];
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }
}

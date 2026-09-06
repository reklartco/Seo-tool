<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerpTop10 extends Model
{
    use HasFactory;

    protected $table = 'serp_top10';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'checked_at' => 'date',
        ];
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }
}

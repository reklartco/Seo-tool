<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageLink extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'nofollow' => 'boolean',
        ];
    }

    public function fromPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'from_page_id');
    }
}

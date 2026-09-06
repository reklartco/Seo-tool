<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSnapshot extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function crawl(): BelongsTo
    {
        return $this->belongsTo(Crawl::class);
    }
}

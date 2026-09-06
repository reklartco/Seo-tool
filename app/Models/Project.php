<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_crawled_at' => 'datetime',
            'gsc_connected_at' => 'datetime',
            'wp_connected_at' => 'datetime',
            'auto_apply_fixes' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function crawls(): HasMany
    {
        return $this->hasMany(Crawl::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(Keyword::class);
    }

    public function fixes(): HasMany
    {
        return $this->hasMany(Fix::class);
    }

    public function gscDaily(): HasMany
    {
        return $this->hasMany(GscDaily::class);
    }

    public function gscQueries(): HasMany
    {
        return $this->hasMany(GscQuery::class);
    }

    public function gscToken(): HasOne
    {
        return $this->hasOne(GscToken::class);
    }

    public function wpConnection(): HasOne
    {
        return $this->hasOne(WpConnection::class);
    }

    public function latestCrawl(): HasOne
    {
        return $this->hasOne(Crawl::class)->latestOfMany();
    }

    public function url(): string
    {
        return $this->protocol.'://'.$this->domain;
    }
}

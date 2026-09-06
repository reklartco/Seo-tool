<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WpConnection extends Model
{
    protected $guarded = [];

    protected $hidden = ['api_key_hash'];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'last_ping_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

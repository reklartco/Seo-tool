<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class Status5xx extends BaseRule
{
    public function key(): string
    {
        return 'status_5xx';
    }

    public function category(): string
    {
        return 'technical';
    }

    public function severity(): string
    {
        return 'critical';
    }

    public function appliesTo(PageContext $ctx): bool
    {
        return true;
    }

    public function check(PageContext $ctx): array
    {
        if ($ctx->statusCode < 500) {
            return [];
        }

        return [$this->issue("Sunucu hatası: {$ctx->statusCode}.", [
            'status' => $ctx->statusCode,
            'expected' => '200 OK',
        ])];
    }
}

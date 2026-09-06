<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class Status4xx extends BaseRule
{
    public function key(): string
    {
        return 'status_4xx';
    }

    public function category(): string
    {
        return 'technical';
    }

    public function severity(): string
    {
        return 'critical';
    }

    public function check(PageContext $ctx): array
    {
        if ($ctx->statusCode < 400 || $ctx->statusCode >= 500) {
            return [];
        }

        return [$this->issue("Sayfa {$ctx->statusCode} döndürüyor.", [
            'status' => $ctx->statusCode,
            'expected' => '200 OK ya da kalıcı yönlendirme',
        ])];
    }
}

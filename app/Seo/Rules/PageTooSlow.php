<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class PageTooSlow extends BaseRule
{
    public const MAX_MS = 3000;

    public function key(): string
    {
        return 'page_too_slow';
    }

    public function category(): string
    {
        return 'technical';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function appliesTo(PageContext $ctx): bool
    {
        return true;
    }

    public function check(PageContext $ctx): array
    {
        if ($ctx->loadTimeMs === 0 || $ctx->loadTimeMs <= self::MAX_MS) {
            return [];
        }

        return [$this->issue('Sayfa '.round($ctx->loadTimeMs / 1000, 1).' saniyede yüklendi.', [
            'load_time_ms' => $ctx->loadTimeMs,
            'expected' => 'En fazla '.(self::MAX_MS / 1000).' saniye',
        ])];
    }
}

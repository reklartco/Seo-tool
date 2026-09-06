<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class HttpsMissing extends BaseRule
{
    public function key(): string
    {
        return 'https_missing';
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
        if ($ctx->isHttps()) {
            return [];
        }

        return [$this->issue('Sayfa HTTPS üzerinden sunulmuyor.', [
            'current' => $ctx->url,
            'expected' => 'https:// üzerinden sunum ve http → https yönlendirmesi',
        ])];
    }
}

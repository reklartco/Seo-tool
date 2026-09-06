<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class CanonicalMissing extends BaseRule
{
    public function key(): string
    {
        return 'canonical_missing';
    }

    public function category(): string
    {
        return 'technical';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function check(PageContext $ctx): array
    {
        if ($ctx->canonical() !== null) {
            return [];
        }

        return [$this->issue('Canonical etiketi yok.', [
            'expected' => 'Sayfanın kendi URL\'sini gösteren bir canonical',
        ])];
    }
}

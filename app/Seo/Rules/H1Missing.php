<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class H1Missing extends BaseRule
{
    public function key(): string
    {
        return 'h1_missing';
    }

    public function category(): string
    {
        return 'content';
    }

    public function severity(): string
    {
        return 'critical';
    }

    public function check(PageContext $ctx): array
    {
        if ($ctx->h1Count() > 0 && $ctx->h1() !== null) {
            return [];
        }

        return [$this->issue('Sayfada H1 başlığı yok.', [
            'current' => null,
            'expected' => 'Sayfa başına tek ve anlamlı bir H1',
        ])];
    }
}

<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class H1Multiple extends BaseRule
{
    public function key(): string
    {
        return 'h1_multiple';
    }

    public function category(): string
    {
        return 'content';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function check(PageContext $ctx): array
    {
        if ($ctx->h1Count() <= 1) {
            return [];
        }

        return [$this->issue('Sayfada '.$ctx->h1Count().' adet H1 var.', [
            'count' => $ctx->h1Count(),
            'expected' => 'Sayfa başına tek H1',
        ])];
    }
}

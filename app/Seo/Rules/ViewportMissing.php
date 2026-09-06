<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class ViewportMissing extends BaseRule
{
    public function key(): string
    {
        return 'viewport_missing';
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
        if ($ctx->viewport() !== null) {
            return [];
        }

        return [$this->issue('Viewport meta etiketi yok, sayfa mobilde bozuk görünür.', [
            'expected' => '<meta name="viewport" content="width=device-width, initial-scale=1">',
        ])];
    }
}

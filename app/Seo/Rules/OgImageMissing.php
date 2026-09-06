<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class OgImageMissing extends BaseRule
{
    public function key(): string
    {
        return 'og_image_missing';
    }

    public function category(): string
    {
        return 'schema';
    }

    public function severity(): string
    {
        return 'notice';
    }

    public function check(PageContext $ctx): array
    {
        if (($ctx->openGraph()['image'] ?? null) !== null) {
            return [];
        }

        return [$this->issue('og:image yok, paylaşımda görsel çıkmaz.', [
            'expected' => '<meta property="og:image" content="...">',
        ])];
    }
}

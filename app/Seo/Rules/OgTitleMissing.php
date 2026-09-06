<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class OgTitleMissing extends BaseRule
{
    public function key(): string
    {
        return 'og_title_missing';
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
        if (($ctx->openGraph()['title'] ?? null) !== null) {
            return [];
        }

        return [$this->issue('og:title yok, sosyal medya paylaşımı zayıf görünür.', [
            'expected' => '<meta property="og:title" content="...">',
        ])];
    }
}

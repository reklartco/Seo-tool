<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class TooManyLinks extends BaseRule
{
    public const MAX_LINKS = 150;

    public function key(): string
    {
        return 'too_many_links';
    }

    public function category(): string
    {
        return 'links';
    }

    public function severity(): string
    {
        return 'notice';
    }

    public function check(PageContext $ctx): array
    {
        $count = count($ctx->links());

        if ($count <= self::MAX_LINKS) {
            return [];
        }

        return [$this->issue("Sayfada {$count} link var, link gücü dağılıyor.", [
            'count' => $count,
            'expected' => 'En fazla '.self::MAX_LINKS.' link',
        ])];
    }
}

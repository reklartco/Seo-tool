<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class MetaDescTooLong extends BaseRule
{
    public const MAX_LENGTH = 160;

    public function key(): string
    {
        return 'meta_desc_too_long';
    }

    public function category(): string
    {
        return 'meta';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function check(PageContext $ctx): array
    {
        $desc = $ctx->metaDescription();

        if ($desc === null || mb_strlen($desc) <= self::MAX_LENGTH) {
            return [];
        }

        return [$this->issue('Meta description '.mb_strlen($desc).' karakter, arama sonucunda kırpılır.', [
            'current' => $desc,
            'length' => mb_strlen($desc),
            'expected' => 'En fazla '.self::MAX_LENGTH.' karakter',
        ])];
    }
}

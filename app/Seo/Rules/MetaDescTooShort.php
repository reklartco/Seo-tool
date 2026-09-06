<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class MetaDescTooShort extends BaseRule
{
    public const MIN_LENGTH = 70;

    public function key(): string
    {
        return 'meta_desc_too_short';
    }

    public function category(): string
    {
        return 'meta';
    }

    public function severity(): string
    {
        return 'notice';
    }

    public function check(PageContext $ctx): array
    {
        $desc = $ctx->metaDescription();

        if ($desc === null || mb_strlen($desc) >= self::MIN_LENGTH) {
            return [];
        }

        return [$this->issue('Meta description '.mb_strlen($desc).' karakter, çok kısa.', [
            'current' => $desc,
            'length' => mb_strlen($desc),
            'expected' => 'En az '.self::MIN_LENGTH.' karakter',
        ])];
    }
}

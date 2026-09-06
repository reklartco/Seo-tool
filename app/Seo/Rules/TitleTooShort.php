<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class TitleTooShort extends BaseRule
{
    public const MIN_LENGTH = 30;

    public function key(): string
    {
        return 'title_too_short';
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
        $title = $ctx->title();

        if ($title === null || mb_strlen($title) >= self::MIN_LENGTH) {
            return [];
        }

        return [$this->issue('Title '.mb_strlen($title).' karakter, çok kısa.', [
            'current' => $title,
            'length' => mb_strlen($title),
            'expected' => 'En az '.self::MIN_LENGTH.' karakter',
        ])];
    }
}

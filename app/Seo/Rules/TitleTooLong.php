<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class TitleTooLong extends BaseRule
{
    public const MAX_LENGTH = 60;

    public function key(): string
    {
        return 'title_too_long';
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

        if ($title === null) {
            return [];
        }

        $length = mb_strlen($title);

        if ($length <= self::MAX_LENGTH) {
            return [];
        }

        return [$this->issue(
            "Title {$length} karakter, arama sonuçlarında kırpılır.",
            [
                'current' => $title,
                'length' => $length,
                'expected' => 'En fazla '.self::MAX_LENGTH.' karakter',
            ],
        )];
    }
}

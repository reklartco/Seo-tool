<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class HtmlTooLarge extends BaseRule
{
    public const MAX_BYTES = 2097152; // 2 MB

    public function key(): string
    {
        return 'html_too_large';
    }

    public function category(): string
    {
        return 'technical';
    }

    public function severity(): string
    {
        return 'notice';
    }

    public function check(PageContext $ctx): array
    {
        if ($ctx->htmlSize() <= self::MAX_BYTES) {
            return [];
        }

        return [$this->issue('HTML boyutu '.round($ctx->htmlSize() / 1048576, 1).' MB.', [
            'bytes' => $ctx->htmlSize(),
            'expected' => 'En fazla 2 MB',
        ])];
    }
}

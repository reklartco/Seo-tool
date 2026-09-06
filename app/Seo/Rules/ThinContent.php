<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class ThinContent extends BaseRule
{
    public const MIN_WORDS = 300;

    public function key(): string
    {
        return 'thin_content';
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
        if ($ctx->statusCode !== 200 || $ctx->words() === 0 || $ctx->words() >= self::MIN_WORDS) {
            return [];
        }

        return [$this->issue('Sayfada yalnızca '.$ctx->words().' kelime var.', [
            'word_count' => $ctx->words(),
            'expected' => 'En az '.self::MIN_WORDS.' kelime',
        ])];
    }
}

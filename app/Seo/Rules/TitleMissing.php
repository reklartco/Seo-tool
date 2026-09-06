<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class TitleMissing extends BaseRule
{
    public function key(): string
    {
        return 'title_missing';
    }

    public function category(): string
    {
        return 'meta';
    }

    public function severity(): string
    {
        return 'critical';
    }

    public function check(PageContext $ctx): array
    {
        if ($ctx->title() !== null) {
            return [];
        }

        return [$this->issue('Sayfada title etiketi yok.', [
            'current' => null,
            'expected' => '30-60 karakter arası, anahtar kelime içeren bir başlık',
        ])];
    }
}

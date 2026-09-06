<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class MetaDescMissing extends BaseRule
{
    public function key(): string
    {
        return 'meta_desc_missing';
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
        if ($ctx->metaDescription() !== null) {
            return [];
        }

        return [$this->issue('Sayfada meta description yok.', [
            'current' => null,
            'expected' => '140-155 karakter, eylem çağrısı içeren bir açıklama',
        ])];
    }
}

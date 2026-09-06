<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class LangAttrMissing extends BaseRule
{
    public function key(): string
    {
        return 'lang_attr_missing';
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
        if ($ctx->lang() !== null) {
            return [];
        }

        return [$this->issue('<html> etiketinde lang özniteliği yok.', [
            'expected' => 'lang="tr" gibi bir dil kodu',
        ])];
    }
}

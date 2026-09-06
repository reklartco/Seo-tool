<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class KeywordMissingInTitle extends BaseRule
{
    public function key(): string
    {
        return 'keyword_missing_in_title';
    }

    public function category(): string
    {
        return 'content';
    }

    public function severity(): string
    {
        return 'notice';
    }

    public function check(PageContext $ctx): array
    {
        $title = $ctx->title();

        if ($title === null || $ctx->projectKeywords === []) {
            return [];
        }

        $haystack = mb_strtolower($title);

        foreach ($ctx->projectKeywords as $keyword) {
            if (str_contains($haystack, mb_strtolower($keyword))) {
                return [];
            }
        }

        return [$this->issue('Title, takip edilen anahtar kelimelerden hiçbirini içermiyor.', [
            'current' => $title,
            'keywords' => array_slice($ctx->projectKeywords, 0, 10),
            'expected' => 'Sayfanın hedef kelimesi title içinde geçmeli',
        ])];
    }
}

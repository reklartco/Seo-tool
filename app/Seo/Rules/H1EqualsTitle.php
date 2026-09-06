<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class H1EqualsTitle extends BaseRule
{
    public function key(): string
    {
        return 'h1_equals_title';
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
        $h1 = $ctx->h1();

        if ($title === null || $h1 === null || mb_strtolower($title) !== mb_strtolower($h1)) {
            return [];
        }

        return [$this->issue('H1 ile title birebir aynı.', [
            'current' => $h1,
            'expected' => 'Title arama sonucuna, H1 sayfaya hitap etmeli; ikisini farklılaştır',
        ])];
    }
}

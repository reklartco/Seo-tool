<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class HeadingHierarchyBroken extends BaseRule
{
    public function key(): string
    {
        return 'heading_hierarchy_broken';
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
        $previous = 0;

        foreach ($ctx->headings() as $heading) {
            $level = $heading['level'];

            if ($previous !== 0 && $level > $previous + 1) {
                return [$this->issue("Başlık sırası atlanmış: H{$previous} sonrası H{$level}.", [
                    'from' => 'H'.$previous,
                    'to' => 'H'.$level,
                    'expected' => 'Başlık seviyeleri sırayla inmeli',
                ])];
            }

            $previous = $level;
        }

        return [];
    }
}

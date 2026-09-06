<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class ImgAltMissing extends BaseRule
{
    public function key(): string
    {
        return 'img_alt_missing';
    }

    public function category(): string
    {
        return 'images';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function check(PageContext $ctx): array
    {
        $issues = [];

        foreach ($ctx->images() as $src => $alt) {
            if ($alt !== null) {
                continue;
            }

            $issues[] = $this->issue('Görselde alt metni yok.', [
                'src' => $src,
                'expected' => 'Görseli tarif eden, anahtar kelime içeren alt metni',
            ]);
        }

        return $issues;
    }
}

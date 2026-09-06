<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class ImgAltEmpty extends BaseRule
{
    public function key(): string
    {
        return 'img_alt_empty';
    }

    public function category(): string
    {
        return 'images';
    }

    public function severity(): string
    {
        return 'notice';
    }

    public function check(PageContext $ctx): array
    {
        $empty = array_values(array_filter(
            $ctx->images(),
            fn (array $image) => $image['alt'] !== null && trim($image['alt']) === '',
        ));

        if ($empty === []) {
            return [];
        }

        return [$this->issue(count($empty).' görselde alt metni boş bırakılmış.', [
            'images' => array_column(array_slice($empty, 0, 10), 'src'),
            'count' => count($empty),
            'expected' => 'Dekoratif olmayan görsellerde açıklayıcı alt metni',
        ])];
    }
}

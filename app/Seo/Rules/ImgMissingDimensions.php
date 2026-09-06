<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class ImgMissingDimensions extends BaseRule
{
    public function key(): string
    {
        return 'img_missing_dimensions';
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
        $missing = array_values(array_filter(
            $ctx->images(),
            fn (array $image) => $image['width'] === null || $image['height'] === null,
        ));

        if ($missing === []) {
            return [];
        }

        return [$this->issue(count($missing).' görselde width/height yok, sayfa yüklenirken kayıyor.', [
            'images' => array_column(array_slice($missing, 0, 10), 'src'),
            'count' => count($missing),
            'expected' => 'Her <img> üzerinde width ve height',
        ])];
    }
}

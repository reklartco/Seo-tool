<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class ImgTooLarge extends BaseRule
{
    public const MAX_BYTES = 307200; // 300 KB

    public function key(): string
    {
        return 'img_too_large';
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

        foreach ($ctx->images() as $image) {
            $bytes = (int) ($image['bytes'] ?? 0);

            if ($bytes <= self::MAX_BYTES) {
                continue;
            }

            $issues[] = $this->issue('Görsel '.round($bytes / 1024).' KB, çok büyük.', [
                'src' => $image['src'],
                'bytes' => $bytes,
                'expected' => 'En fazla 300 KB (WebP önerilir)',
            ]);
        }

        return $issues;
    }
}

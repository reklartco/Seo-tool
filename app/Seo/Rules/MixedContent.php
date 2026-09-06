<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class MixedContent extends BaseRule
{
    public function key(): string
    {
        return 'mixed_content';
    }

    public function category(): string
    {
        return 'technical';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function check(PageContext $ctx): array
    {
        if (! $ctx->isHttps()) {
            return [];
        }

        $insecure = array_values(array_filter(
            $ctx->resources(),
            fn (string $url) => str_starts_with(mb_strtolower($url), 'http://'),
        ));

        if ($insecure === []) {
            return [];
        }

        return [$this->issue(count($insecure).' kaynak HTTPS sayfada http:// üzerinden yükleniyor.', [
            'resources' => array_slice($insecure, 0, 10),
            'count' => count($insecure),
            'expected' => 'Tüm kaynaklar https:// üzerinden',
        ])];
    }
}

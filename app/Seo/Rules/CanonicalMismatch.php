<?php

namespace App\Seo\Rules;

use App\Seo\Crawler\UrlNormalizer;
use App\Seo\PageContext;

class CanonicalMismatch extends BaseRule
{
    public function key(): string
    {
        return 'canonical_mismatch';
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
        $canonical = $ctx->canonical();

        if ($canonical === null) {
            return [];
        }

        $absolute = UrlNormalizer::absolute($canonical, $ctx->url);
        $domain = $ctx->domain ?: (parse_url($ctx->url, PHP_URL_HOST) ?? '');

        if ($absolute === null || ! UrlNormalizer::sameSite($absolute, $domain)) {
            return [$this->issue('Canonical başka bir alan adını gösteriyor.', [
                'current' => $canonical,
                'expected' => 'Aynı alan adı içinde bir URL',
            ])];
        }

        if ($absolute === UrlNormalizer::normalize($ctx->url)) {
            return [];
        }

        return [$this->issue('Canonical başka bir sayfayı gösteriyor.', [
            'current' => $absolute,
            'page' => $ctx->url,
            'expected' => 'Kendi kendini gösteren canonical (bilinçli değilse)',
        ])];
    }
}

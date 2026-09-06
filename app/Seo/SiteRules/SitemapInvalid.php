<?php

namespace App\Seo\SiteRules;

use App\Seo\SiteContext;

class SitemapInvalid extends BaseSiteRule
{
    public function key(): string
    {
        return 'sitemap_invalid';
    }

    public function category(): string
    {
        return 'technical';
    }

    public function severity(): string
    {
        return 'critical';
    }

    public function check(SiteContext $ctx): array
    {
        if (! ($ctx->sitemap['found'] ?? false) || ($ctx->sitemap['valid'] ?? true)) {
            return [];
        }

        return [$this->issue('sitemap.xml okunamıyor (geçersiz XML).', [
            'expected' => 'Geçerli XML sitemap',
        ])];
    }
}

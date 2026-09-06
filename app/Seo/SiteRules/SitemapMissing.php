<?php

namespace App\Seo\SiteRules;

use App\Seo\SiteContext;

class SitemapMissing extends BaseSiteRule
{
    public function key(): string
    {
        return 'sitemap_missing';
    }

    public function category(): string
    {
        return 'technical';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function check(SiteContext $ctx): array
    {
        if ($ctx->sitemap['found'] ?? false) {
            return [];
        }

        return [$this->issue('sitemap.xml bulunamadı.', [
            'url' => $ctx->project->url().'/sitemap.xml',
            'expected' => 'Yayındaki sayfaları listeleyen bir sitemap',
        ])];
    }
}

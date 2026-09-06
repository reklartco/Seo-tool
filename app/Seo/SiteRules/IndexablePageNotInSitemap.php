<?php

namespace App\Seo\SiteRules;

use App\Seo\SiteContext;

class IndexablePageNotInSitemap extends BaseSiteRule
{
    public function key(): string
    {
        return 'indexable_page_not_in_sitemap';
    }

    public function category(): string
    {
        return 'technical';
    }

    public function severity(): string
    {
        return 'notice';
    }

    public function check(SiteContext $ctx): array
    {
        if (! ($ctx->sitemap['found'] ?? false)) {
            return [];
        }

        $issues = [];

        foreach ($ctx->pages as $page) {
            if (! $page->indexable || $page->in_sitemap || $page->status_code !== 200) {
                continue;
            }

            $issues[] = $this->issue('İndekslenebilir sayfa sitemap içinde yok.', [
                'url' => $page->url,
                'expected' => 'Yayındaki tüm sayfalar sitemap.xml içinde',
            ], $page->id);
        }

        return $issues;
    }
}

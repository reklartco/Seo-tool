<?php

namespace App\Seo\SiteRules;

use App\Seo\SiteContext;

class NoindexInSitemap extends BaseSiteRule
{
    public function key(): string
    {
        return 'noindex_in_sitemap';
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
        $issues = [];

        foreach ($ctx->pages as $page) {
            if ($page->indexable || ! $page->in_sitemap) {
                continue;
            }

            $issues[] = $this->issue('Noindex sayfa sitemap içinde listelenmiş.', [
                'url' => $page->url,
                'robots' => $page->robots_meta,
                'expected' => 'Noindex sayfaları sitemap dışında bırak',
            ], $page->id);
        }

        return $issues;
    }
}

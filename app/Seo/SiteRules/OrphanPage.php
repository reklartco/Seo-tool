<?php

namespace App\Seo\SiteRules;

use App\Seo\Crawler\UrlNormalizer;
use App\Seo\SiteContext;

class OrphanPage extends BaseSiteRule
{
    public function key(): string
    {
        return 'orphan_page';
    }

    public function category(): string
    {
        return 'links';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function check(SiteContext $ctx): array
    {
        $home = UrlNormalizer::normalize($ctx->project->url());

        $issues = [];

        foreach ($ctx->pages as $page) {
            if ($page->internal_links_in > 0 || $page->url === $home || $page->status_code !== 200) {
                continue;
            }

            $issues[] = $this->issue('Bu sayfaya site içinden hiç link verilmiyor.', [
                'url' => $page->url,
                'expected' => 'İlgili sayfalardan en az bir iç link',
            ], $page->id);
        }

        return $issues;
    }
}

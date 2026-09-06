<?php

namespace App\Seo\SiteRules;

use App\Seo\SiteContext;

class RobotsTxtBlocksAll extends BaseSiteRule
{
    public function key(): string
    {
        return 'robots_txt_blocks_all';
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
        if (! ($ctx->robots['blocks_all'] ?? false)) {
            return [];
        }

        return [$this->issue('robots.txt tüm siteyi aramaya kapatıyor.', [
            'expected' => 'Disallow: / satırını kaldır',
        ])];
    }
}

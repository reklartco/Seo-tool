<?php

namespace App\Seo\SiteRules;

use App\Seo\SiteContext;

class RobotsTxtMissing extends BaseSiteRule
{
    public function key(): string
    {
        return 'robots_txt_missing';
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
        if (($ctx->robots['status'] ?? 0) === 200) {
            return [];
        }

        return [$this->issue('robots.txt bulunamadı.', [
            'url' => $ctx->project->url().'/robots.txt',
            'status' => $ctx->robots['status'] ?? null,
            'expected' => 'Sitemap satırı içeren bir robots.txt',
        ])];
    }
}

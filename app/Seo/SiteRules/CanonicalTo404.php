<?php

namespace App\Seo\SiteRules;

use App\Seo\Crawler\UrlNormalizer;
use App\Seo\SiteContext;

class CanonicalTo404 extends BaseSiteRule
{
    public function key(): string
    {
        return 'canonical_to_404';
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
        $statuses = $ctx->pages->pluck('status_code', 'url');
        $issues = [];

        foreach ($ctx->pages as $page) {
            if (blank($page->canonical)) {
                continue;
            }

            $target = UrlNormalizer::absolute($page->canonical, $page->url);

            if ($target === null || ! $statuses->has($target)) {
                continue;
            }

            $status = (int) $statuses->get($target);

            if ($status < 400) {
                continue;
            }

            $issues[] = $this->issue("Canonical {$status} dönen bir sayfayı gösteriyor.", [
                'canonical' => $target,
                'status' => $status,
                'expected' => 'Yayında olan bir sayfaya işaret eden canonical',
            ], $page->id);
        }

        return $issues;
    }
}

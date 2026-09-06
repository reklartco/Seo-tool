<?php

namespace App\Seo\SiteRules;

use App\Seo\SiteContext;

class BrokenExternalLink extends BaseSiteRule
{
    public function key(): string
    {
        return 'broken_external_link';
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
        $issues = [];

        foreach ($ctx->brokenLinks as $link) {
            if ($link['is_internal']) {
                continue;
            }

            $issues[] = $this->issue('Kırık dış link: '.$link['to_url'], [
                'to_url' => $link['to_url'],
                'status' => $link['status'],
                'expected' => 'Linki güncelle ya da kaldır',
            ], $link['from_page_id']);
        }

        return $issues;
    }
}

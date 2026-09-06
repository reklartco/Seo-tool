<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class RedirectChain extends BaseRule
{
    public const MAX_HOPS = 2;

    public function key(): string
    {
        return 'redirect_chain';
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
        if ($ctx->redirectHops <= self::MAX_HOPS) {
            return [];
        }

        return [$this->issue("Sayfaya ulaşmak için {$ctx->redirectHops} yönlendirme gerekiyor.", [
            'hops' => $ctx->redirectHops,
            'expected' => 'En fazla '.self::MAX_HOPS.' yönlendirme',
        ])];
    }
}

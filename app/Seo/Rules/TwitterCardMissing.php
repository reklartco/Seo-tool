<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class TwitterCardMissing extends BaseRule
{
    public function key(): string
    {
        return 'twitter_card_missing';
    }

    public function category(): string
    {
        return 'schema';
    }

    public function severity(): string
    {
        return 'notice';
    }

    public function check(PageContext $ctx): array
    {
        if ($ctx->twitterCard() !== null) {
            return [];
        }

        return [$this->issue('twitter:card etiketi yok.', [
            'expected' => '<meta name="twitter:card" content="summary_large_image">',
        ])];
    }
}

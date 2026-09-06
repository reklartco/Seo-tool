<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class LinkTextGeneric extends BaseRule
{
    public const GENERIC = [
        'tıkla', 'tıklayın', 'buraya', 'buraya tıklayın', 'devamı', 'devamını oku',
        'daha fazla', 'oku', 'link', 'bağlantı', 'click here', 'read more', 'more',
    ];

    public function key(): string
    {
        return 'link_text_generic';
    }

    public function category(): string
    {
        return 'links';
    }

    public function severity(): string
    {
        return 'notice';
    }

    public function check(PageContext $ctx): array
    {
        $generic = [];

        foreach ($ctx->links() as $link) {
            $anchor = mb_strtolower(trim($link['anchor']));

            if ($anchor !== '' && in_array($anchor, self::GENERIC, true)) {
                $generic[] = $link['url'];
            }
        }

        if ($generic === []) {
            return [];
        }

        return [$this->issue(count($generic).' linkte açıklayıcı olmayan bağlantı metni var.', [
            'links' => array_slice($generic, 0, 10),
            'count' => count($generic),
            'expected' => 'Hedef sayfayı anlatan bağlantı metni',
        ])];
    }
}

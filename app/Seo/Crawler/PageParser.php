<?php

namespace App\Seo\Crawler;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Turns raw HTML into the flat array every rule reads through PageContext.
 *
 * Parsing happens once per page: rules never touch the DOM themselves.
 */
class PageParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $html, string $baseUrl): array
    {
        $crawler = new Crawler(null, $baseUrl);
        $crawler->addHtmlContent($html, 'UTF-8');

        $text = $this->bodyText($crawler);

        return [
            'title' => $this->first($crawler, 'head title'),
            'meta_description' => $this->metaContent($crawler, 'description'),
            'robots_meta' => $this->metaContent($crawler, 'robots'),
            'canonical' => $this->attr($crawler, 'link[rel="canonical"]', 'href'),
            'lang' => $this->attr($crawler, 'html', 'lang'),
            'viewport' => $this->metaContent($crawler, 'viewport'),
            'h1' => $this->first($crawler, 'h1'),
            'h1_count' => $crawler->filter('h1')->count(),
            'headings' => $this->headings($crawler),
            'images' => $this->images($crawler),
            'links' => $this->links($crawler, $baseUrl),
            'og' => $this->openGraph($crawler),
            'twitter_card' => $this->attr($crawler, 'meta[name="twitter:card"]', 'content'),
            'schema' => $this->schema($crawler),
            'word_count' => $this->wordCount($text),
            'text' => mb_substr($text, 0, 2000),
            'html_size' => strlen($html),
            'content_hash' => sha1(preg_replace('/\s+/u', ' ', $text) ?? $text),
            'resources' => $this->resources($crawler),
        ];
    }

    private function bodyText(Crawler $crawler): string
    {
        $body = $crawler->filter('body');

        if ($body->count() === 0) {
            return '';
        }

        // Script and style content is not page copy.
        $html = $body->html();
        $html = preg_replace('#<(script|style|noscript)\b[^>]*>.*?</\1>#is', ' ', $html) ?? $html;

        return trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
    }

    private function wordCount(string $text): int
    {
        return $text === '' ? 0 : count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    /**
     * @return list<array{level: int, text: string}>
     */
    private function headings(Crawler $crawler): array
    {
        $headings = [];

        foreach ($crawler->filter('h1, h2, h3, h4, h5, h6') as $node) {
            $headings[] = [
                'level' => (int) substr($node->nodeName, 1),
                'text' => $this->clean($node->textContent) ?? '',
            ];
        }

        return $headings;
    }

    /**
     * @return list<array{src: string, alt: string|null, width: string|null, height: string|null}>
     */
    private function images(Crawler $crawler): array
    {
        $images = [];

        foreach ($crawler->filter('img') as $node) {
            $src = $node->getAttribute('src') ?: $node->getAttribute('data-src');

            if ($src === '') {
                continue;
            }

            $images[] = [
                'src' => $src,
                'alt' => $node->hasAttribute('alt') ? $node->getAttribute('alt') : null,
                'width' => $node->getAttribute('width') ?: null,
                'height' => $node->getAttribute('height') ?: null,
            ];
        }

        return $images;
    }

    /**
     * @return list<array{url: string, anchor: string, nofollow: bool}>
     */
    private function links(Crawler $crawler, string $baseUrl): array
    {
        $links = [];

        foreach ($crawler->filter('a[href]') as $node) {
            $href = trim($node->getAttribute('href'));

            if ($href === '' || str_starts_with($href, '#') || preg_match('#^(mailto|tel|javascript|data):#i', $href)) {
                continue;
            }

            $absolute = UrlNormalizer::absolute($href, $baseUrl);

            if ($absolute === null) {
                continue;
            }

            $links[] = [
                'url' => $absolute,
                'anchor' => $this->clean($node->textContent) ?? '',
                'nofollow' => str_contains(mb_strtolower($node->getAttribute('rel')), 'nofollow'),
            ];
        }

        return $links;
    }

    /**
     * @return array<string, string|null>
     */
    private function openGraph(Crawler $crawler): array
    {
        $og = [];

        foreach ($crawler->filter('meta[property^="og:"]') as $node) {
            $og[substr($node->getAttribute('property'), 3)] = $this->clean($node->getAttribute('content'));
        }

        return $og;
    }

    /**
     * @return array{blocks: int, invalid: int, types: list<string>}
     */
    private function schema(Crawler $crawler): array
    {
        $blocks = 0;
        $invalid = 0;
        $types = [];

        foreach ($crawler->filter('script[type="application/ld+json"]') as $node) {
            $blocks++;
            $decoded = json_decode($node->textContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $invalid++;

                continue;
            }

            foreach ((array) $decoded as $key => $value) {
                if ($key === '@type' && is_string($value)) {
                    $types[] = $value;
                } elseif (is_array($value) && isset($value['@type']) && is_string($value['@type'])) {
                    $types[] = $value['@type'];
                }
            }
        }

        // Microdata fallback for themes that do not ship JSON-LD.
        foreach ($crawler->filter('[itemtype]') as $node) {
            $types[] = basename($node->getAttribute('itemtype'));
        }

        return ['blocks' => $blocks, 'invalid' => $invalid, 'types' => array_values(array_unique($types))];
    }

    /**
     * Sub-resource URLs, used by the mixed-content rule.
     *
     * @return list<string>
     */
    private function resources(Crawler $crawler): array
    {
        $urls = [];

        foreach ($crawler->filter('img[src], script[src], link[rel="stylesheet"][href], iframe[src]') as $node) {
            $url = $node->getAttribute('src') ?: $node->getAttribute('href');

            if ($url !== '') {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    private function first(Crawler $crawler, string $selector): ?string
    {
        $node = $crawler->filter($selector);

        return $node->count() ? $this->clean($node->first()->text('')) : null;
    }

    private function attr(Crawler $crawler, string $selector, string $attribute): ?string
    {
        $node = $crawler->filter($selector);

        return $node->count() ? $this->clean($node->first()->attr($attribute) ?? '') : null;
    }

    private function metaContent(Crawler $crawler, string $name): ?string
    {
        return $this->attr($crawler, 'meta[name="'.$name.'"]', 'content');
    }

    private function clean(?string $value): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');

        return $value === '' ? null : $value;
    }
}

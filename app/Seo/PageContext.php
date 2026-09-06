<?php

namespace App\Seo;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Everything a rule needs to judge a single crawled page.
 *
 * The crawler instance is lazily built from the raw HTML so rules that only
 * look at scalar values (status code, word count) never pay for parsing.
 */
class PageContext
{
    private ?Crawler $crawler = null;

    /**
     * @param  array<string, mixed>  $meta
     * @param  list<string>  $projectKeywords
     */
    public function __construct(
        public readonly string $url,
        public readonly string $html = '',
        public readonly int $statusCode = 200,
        public readonly array $meta = [],
        public readonly int $wordCount = 0,
        public readonly int $loadTimeMs = 0,
        public readonly int $depth = 0,
        public readonly array $projectKeywords = [],
    ) {}

    public function crawler(): Crawler
    {
        return $this->crawler ??= new Crawler($this->html, $this->url);
    }

    public function title(): ?string
    {
        return $this->text('title') ?? $this->firstNodeText('title');
    }

    public function metaDescription(): ?string
    {
        return $this->text('meta_description') ?? $this->metaContent('description');
    }

    public function h1(): ?string
    {
        return $this->text('h1') ?? $this->firstNodeText('h1');
    }

    public function h1Count(): int
    {
        return isset($this->meta['h1_count'])
            ? (int) $this->meta['h1_count']
            : $this->crawler()->filter('h1')->count();
    }

    public function canonical(): ?string
    {
        if (isset($this->meta['canonical'])) {
            return $this->normalize((string) $this->meta['canonical']);
        }

        $node = $this->crawler()->filter('link[rel="canonical"]');

        return $node->count() ? $this->normalize($node->attr('href') ?? '') : null;
    }

    /**
     * Images with their alt attribute, as ['src' => 'alt|null'].
     *
     * @return array<string, string|null>
     */
    public function images(): array
    {
        if (isset($this->meta['images']) && is_array($this->meta['images'])) {
            return $this->meta['images'];
        }

        if ($this->html === '') {
            return [];
        }

        $images = [];

        foreach ($this->crawler()->filter('img') as $node) {
            $src = $node->getAttribute('src');

            if ($src === '') {
                continue;
            }

            $images[$src] = $node->hasAttribute('alt') ? $node->getAttribute('alt') : null;
        }

        return $images;
    }

    private function text(string $key): ?string
    {
        return $this->normalize((string) ($this->meta[$key] ?? ''));
    }

    private function firstNodeText(string $selector): ?string
    {
        if ($this->html === '') {
            return null;
        }

        $node = $this->crawler()->filter($selector);

        return $node->count() ? $this->normalize($node->first()->text('')) : null;
    }

    private function metaContent(string $name): ?string
    {
        if ($this->html === '') {
            return null;
        }

        $node = $this->crawler()->filter('meta[name="'.$name.'"]');

        return $node->count() ? $this->normalize($node->attr('content') ?? '') : null;
    }

    private function normalize(string $value): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value === '' ? null : $value;
    }
}

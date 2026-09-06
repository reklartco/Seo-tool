<?php

namespace App\Seo;

use App\Seo\Crawler\PageParser;
use App\Seo\Crawler\UrlNormalizer;

/**
 * Everything a rule needs to judge a single crawled page.
 *
 * The crawler hands over an already parsed payload; when a rule is exercised
 * with raw HTML (tests, one-off checks) the payload is parsed on demand.
 */
class PageContext
{
    /** @var array<string, mixed>|null */
    private ?array $parsed = null;

    /**
     * @param  array<string, mixed>  $meta  Parsed payload from PageParser.
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
        public readonly int $redirectHops = 0,
        public readonly string $domain = '',
        public readonly string $cms = 'custom',
        public readonly ?string $contentType = 'text/html',
        public readonly ?string $error = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function parsed(): array
    {
        if ($this->parsed !== null) {
            return $this->parsed;
        }

        if ($this->meta !== []) {
            return $this->parsed = $this->meta;
        }

        return $this->parsed = $this->html === ''
            ? []
            : (new PageParser)->parse($this->html, $this->url);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->parsed()[$key] ?? $default;
    }

    public function title(): ?string
    {
        return $this->get('title');
    }

    public function metaDescription(): ?string
    {
        return $this->get('meta_description');
    }

    public function h1(): ?string
    {
        return $this->get('h1');
    }

    public function h1Count(): int
    {
        return (int) $this->get('h1_count', 0);
    }

    public function canonical(): ?string
    {
        return $this->get('canonical');
    }

    public function robotsMeta(): ?string
    {
        return $this->get('robots_meta');
    }

    public function isIndexable(): bool
    {
        return ! str_contains(mb_strtolower($this->robotsMeta() ?? ''), 'noindex');
    }

    public function lang(): ?string
    {
        return $this->get('lang');
    }

    public function viewport(): ?string
    {
        return $this->get('viewport');
    }

    public function words(): int
    {
        return $this->wordCount ?: (int) $this->get('word_count', 0);
    }

    public function htmlSize(): int
    {
        return (int) $this->get('html_size', strlen($this->html));
    }

    public function contentHash(): ?string
    {
        return $this->get('content_hash');
    }

    public function text(): string
    {
        return (string) $this->get('text', '');
    }

    /**
     * @return list<array{level: int, text: string}>
     */
    public function headings(): array
    {
        return $this->get('headings', []);
    }

    /**
     * @return list<array{src: string, alt: string|null, width: string|null, height: string|null}>
     */
    public function images(): array
    {
        return $this->get('images', []);
    }

    /**
     * @return list<array{url: string, anchor: string, nofollow: bool}>
     */
    public function links(): array
    {
        return $this->get('links', []);
    }

    /**
     * @return list<array{url: string, anchor: string, nofollow: bool}>
     */
    public function internalLinks(): array
    {
        $domain = $this->domain ?: (parse_url($this->url, PHP_URL_HOST) ?? '');

        return array_values(array_filter(
            $this->links(),
            fn (array $link) => UrlNormalizer::sameSite($link['url'], $domain),
        ));
    }

    /**
     * @return list<string>
     */
    public function resources(): array
    {
        return $this->get('resources', []);
    }

    /**
     * @return array<string, string|null>
     */
    public function openGraph(): array
    {
        return $this->get('og', []);
    }

    public function twitterCard(): ?string
    {
        return $this->get('twitter_card');
    }

    /**
     * @return array{blocks: int, invalid: int, types: list<string>}
     */
    public function schema(): array
    {
        return $this->get('schema', ['blocks' => 0, 'invalid' => 0, 'types' => []]);
    }

    public function isHttps(): bool
    {
        return str_starts_with(mb_strtolower($this->url), 'https://');
    }
}

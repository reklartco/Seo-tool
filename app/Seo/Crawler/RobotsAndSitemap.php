<?php

namespace App\Seo\Crawler;

/**
 * Reads robots.txt and every sitemap it points at (plus the conventional
 * /sitemap.xml), returning the URL set the crawler starts from.
 */
class RobotsAndSitemap
{
    public function __construct(private readonly HttpFetcher $fetcher) {}

    /**
     * @return array{status: int, body: string, blocks_all: bool, sitemaps: list<string>, disallow: list<string>}
     */
    public function robots(string $origin): array
    {
        $result = $this->fetcher->get(rtrim($origin, '/').'/robots.txt', 10);

        $sitemaps = [];
        $disallow = [];
        $blocksAll = false;
        $appliesToUs = true;

        foreach (preg_split('/\R/', $result->body) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, ':')) {
                continue;
            }

            [$directive, $value] = array_map('trim', explode(':', $line, 2));
            $directive = mb_strtolower($directive);

            if ($directive === 'sitemap') {
                $sitemaps[] = $value;

                continue;
            }

            if ($directive === 'user-agent') {
                $appliesToUs = in_array($value, ['*', 'SeoBot'], true);

                continue;
            }

            if ($directive === 'disallow' && $appliesToUs) {
                $disallow[] = $value;

                if ($value === '/') {
                    $blocksAll = true;
                }
            }
        }

        return [
            'status' => $result->statusCode,
            'body' => mb_substr($result->body, 0, 20000),
            'blocks_all' => $result->statusCode === 200 && $blocksAll,
            'sitemaps' => $sitemaps,
            'disallow' => array_values(array_filter($disallow)),
        ];
    }

    /**
     * @param  list<string>  $sitemapUrls
     * @return array{found: bool, valid: bool, urls: list<string>}
     */
    public function sitemap(string $origin, array $sitemapUrls = [], int $limit = 5000): array
    {
        $candidates = $sitemapUrls !== [] ? $sitemapUrls : [rtrim($origin, '/').'/sitemap.xml'];

        $found = false;
        $valid = true;
        $urls = [];
        $seen = [];

        foreach ($candidates as $candidate) {
            $this->readSitemap($candidate, $urls, $seen, $found, $valid, $limit, 0);
        }

        return ['found' => $found, 'valid' => $valid, 'urls' => array_values(array_unique($urls))];
    }

    /**
     * @param  list<string>  $urls
     * @param  array<string, true>  $seen
     */
    private function readSitemap(string $url, array &$urls, array &$seen, bool &$found, bool &$valid, int $limit, int $depth): void
    {
        if ($depth > 3 || count($urls) >= $limit || isset($seen[$url])) {
            return;
        }

        $seen[$url] = true;
        $result = $this->fetcher->get($url, 20);

        if (! $result->ok() || trim($result->body) === '') {
            return;
        }

        $found = true;

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($result->body);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            $valid = false;

            return;
        }

        // Sitemap index: recurse into each child sitemap.
        if ($xml->getName() === 'sitemapindex') {
            foreach ($xml->sitemap as $child) {
                $this->readSitemap(trim((string) $child->loc), $urls, $seen, $found, $valid, $limit, $depth + 1);
            }

            return;
        }

        foreach ($xml->url as $entry) {
            $loc = UrlNormalizer::normalize(trim((string) $entry->loc));

            if ($loc !== null) {
                $urls[] = $loc;
            }

            if (count($urls) >= $limit) {
                return;
            }
        }
    }

    /**
     * @param  list<string>  $disallow
     */
    public static function isAllowed(string $url, array $disallow): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        foreach ($disallow as $rule) {
            if ($rule !== '' && str_starts_with($path, rtrim($rule, '*'))) {
                return false;
            }
        }

        return true;
    }
}

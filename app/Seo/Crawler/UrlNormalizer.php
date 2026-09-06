<?php

namespace App\Seo\Crawler;

class UrlNormalizer
{
    /**
     * Resolve a href against the page it was found on.
     */
    public static function absolute(string $href, string $baseUrl): ?string
    {
        if (preg_match('#^https?://#i', $href)) {
            return self::normalize($href);
        }

        $base = parse_url($baseUrl);

        if (! isset($base['scheme'], $base['host'])) {
            return null;
        }

        $origin = $base['scheme'].'://'.$base['host'].(isset($base['port']) ? ':'.$base['port'] : '');

        if (str_starts_with($href, '//')) {
            return self::normalize($base['scheme'].':'.$href);
        }

        if (str_starts_with($href, '/')) {
            return self::normalize($origin.$href);
        }

        $path = $base['path'] ?? '/';
        $dir = str_ends_with($path, '/') ? $path : dirname($path).'/';

        return self::normalize($origin.self::collapse($dir.$href));
    }

    /**
     * Drop the fragment and tracking noise, lowercase the host, and strip a
     * trailing slash so the same page is not crawled twice.
     */
    public static function normalize(string $url): ?string
    {
        $parts = parse_url(trim($url));

        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $query = '';

        if (isset($parts['query'])) {
            parse_str($parts['query'], $params);

            foreach (array_keys($params) as $key) {
                if (preg_match('/^(utm_[a-z0-9_]*|fbclid|gclid|msclkid|ref)$/i', (string) $key)) {
                    unset($params[$key]);
                }
            }

            ksort($params);
            $query = $params ? '?'.http_build_query($params) : '';
        }

        $path = self::collapse($parts['path'] ?? '/');

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        return mb_strtolower($parts['scheme']).'://'.mb_strtolower($parts['host'])
            .(isset($parts['port']) ? ':'.$parts['port'] : '').$path.$query;
    }

    /** Resolve ./ and ../ segments. */
    private static function collapse(string $path): string
    {
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '.' || $segment === '') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        return '/'.implode('/', $segments).(str_ends_with($path, '/') && $segments ? '/' : '');
    }

    public static function host(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host ? preg_replace('/^www\./i', '', mb_strtolower($host)) : null;
    }

    public static function sameSite(string $url, string $domain): bool
    {
        $host = self::host($url);
        $domain = preg_replace('/^www\./i', '', mb_strtolower($domain));

        return $host !== null && ($host === $domain || str_ends_with($host, '.'.$domain));
    }
}

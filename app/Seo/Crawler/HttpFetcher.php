<?php

namespace App\Seo\Crawler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;

/**
 * Thin Guzzle wrapper: one request in, one FetchResult out.
 *
 * Redirects are followed but counted, so redirect_chain / redirect_loop
 * rules can see how many hops the page needed.
 */
class HttpFetcher
{
    public const USER_AGENT = 'SeoBot/1.0 (+https://seo.reklart.co/bot)';

    public function __construct(private readonly ?Client $client = null) {}

    public function get(string $url, int $timeout = 15): FetchResult
    {
        return $this->request('GET', $url, $timeout);
    }

    public function head(string $url, int $timeout = 10): FetchResult
    {
        return $this->request('HEAD', $url, $timeout);
    }

    private function request(string $method, string $url, int $timeout): FetchResult
    {
        $client = $this->client ?? new Client;

        $finalUrl = $url;
        $started = microtime(true);
        $hops = 0;

        try {
            $response = $client->request($method, $url, [
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::TIMEOUT => $timeout,
                RequestOptions::CONNECT_TIMEOUT => min(10, $timeout),
                RequestOptions::ALLOW_REDIRECTS => [
                    'max' => 6,
                    'strict' => true,
                    'referer' => false,
                    'track_redirects' => true,
                ],
                RequestOptions::HEADERS => [
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'tr,en;q=0.8',
                ],
                RequestOptions::ON_STATS => function (TransferStats $stats) use (&$finalUrl) {
                    $finalUrl = (string) $stats->getEffectiveUri();
                },
            ]);

            $hops = count($response->getHeader('X-Guzzle-Redirect-History'));

            return new FetchResult(
                url: $url,
                finalUrl: $finalUrl,
                statusCode: $response->getStatusCode(),
                body: (string) $response->getBody(),
                headers: $response->getHeaders(),
                loadTimeMs: (int) round((microtime(true) - $started) * 1000),
                redirectHops: $hops,
            );
        } catch (ConnectException|RequestException $e) {
            return new FetchResult(
                url: $url,
                finalUrl: $finalUrl,
                statusCode: 0,
                loadTimeMs: (int) round((microtime(true) - $started) * 1000),
                redirectHops: $hops,
                error: $e->getMessage(),
            );
        }
    }
}

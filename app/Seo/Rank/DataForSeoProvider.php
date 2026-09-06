<?php

namespace App\Seo\Rank;

use App\Seo\Crawler\UrlNormalizer;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

/**
 * DataForSEO live SERP + keyword data.
 *
 * Google is never scraped directly; rank data only ever comes through a
 * RankProvider implementation like this one.
 */
class DataForSeoProvider implements RankProvider
{
    public const BASE_URL = 'https://api.dataforseo.com/v3';

    public const DEPTH = 100;

    public function __construct(
        private readonly HttpFactory $http,
        private readonly ?string $login = null,
        private readonly ?string $password = null,
    ) {}

    public function fetchRank(string $keyword, string $domain, Location $location): SerpResult
    {
        $items = $this->post('/serp/google/organic/live/advanced', [[
            'keyword' => $keyword,
            'location_code' => $location->locationCode,
            'language_code' => $location->language,
            'device' => $location->device,
            'depth' => self::DEPTH,
        ]]);

        $rank = null;
        $rankUrl = null;
        $top = [];
        $features = [];

        foreach ($items as $item) {
            $type = $item['type'] ?? '';

            if ($type !== 'organic') {
                $features[] = $type;

                continue;
            }

            $position = (int) ($item['rank_absolute'] ?? 0);
            $url = (string) ($item['url'] ?? '');
            $itemDomain = UrlNormalizer::host($url) ?? (string) ($item['domain'] ?? '');

            if (count($top) < 10) {
                $top[] = [
                    'position' => $position,
                    'domain' => $itemDomain,
                    'url' => $url,
                    'title' => $item['title'] ?? null,
                ];
            }

            if ($rank === null && $this->matches($itemDomain, $domain)) {
                $rank = $position;
                $rankUrl = $url;
            }
        }

        return new SerpResult(
            keyword: $keyword,
            rank: $rank,
            url: $rankUrl,
            top: $top,
            features: array_values(array_unique($features)),
        );
    }

    public function fetchVolume(array $keywords, Location $location): array
    {
        if ($keywords === []) {
            return [];
        }

        $items = $this->post('/keywords_data/google_ads/search_volume/live', [[
            'keywords' => array_values(array_map('mb_strtolower', $keywords)),
            'location_code' => $location->locationCode,
            'language_code' => $location->language,
        ]]);

        $volumes = [];

        foreach ($items as $item) {
            $volumes[mb_strtolower((string) ($item['keyword'] ?? ''))] = [
                'search_volume' => isset($item['search_volume']) ? (int) $item['search_volume'] : null,
                'cpc' => isset($item['cpc']) ? (float) $item['cpc'] : null,
                'competition' => isset($item['competition_index'])
                    ? round($item['competition_index'] / 100, 3)
                    : (isset($item['competition']) ? (float) $item['competition'] : null),
            ];
        }

        return $volumes;
    }

    /**
     * @param  list<array<string, mixed>>  $payload
     * @return list<array<string, mixed>>
     */
    private function post(string $path, array $payload): array
    {
        $login = $this->login ?? config('services.dataforseo.login');
        $password = $this->password ?? config('services.dataforseo.password');

        if (blank($login) || blank($password)) {
            throw new RuntimeException('DataForSEO kimlik bilgileri tanımlı değil (.env: DATAFORSEO_LOGIN / DATAFORSEO_PASSWORD).');
        }

        $response = $this->http
            ->withBasicAuth($login, $password)
            ->timeout(90)
            ->retry(2, 500)
            ->post(self::BASE_URL.$path, $payload);

        if ($response->failed()) {
            throw new RuntimeException('DataForSEO isteği başarısız: HTTP '.$response->status());
        }

        $task = $response->json('tasks.0');

        if (($task['status_code'] ?? 0) !== 20000) {
            throw new RuntimeException('DataForSEO hatası: '.($task['status_message'] ?? 'bilinmiyor'));
        }

        return $task['result'][0]['items'] ?? $task['result'] ?? [];
    }

    private function matches(string $itemDomain, string $projectDomain): bool
    {
        $itemDomain = preg_replace('/^www\./i', '', mb_strtolower($itemDomain));
        $projectDomain = preg_replace('/^www\./i', '', mb_strtolower($projectDomain));

        return $itemDomain === $projectDomain || str_ends_with($itemDomain, '.'.$projectDomain);
    }
}

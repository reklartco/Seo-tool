<?php

namespace App\Services\Google;

use App\Models\GscToken;
use App\Models\Project;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

/**
 * Minimal Search Console client: OAuth2 authorization code flow plus the
 * two endpoints the MVP needs (site list and searchAnalytics.query).
 */
class SearchConsoleClient
{
    public const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    public const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public const API_URL = 'https://www.googleapis.com/webmasters/v3';

    public const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';

    public function __construct(private readonly HttpFactory $http) {}

    public function configured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function authorizationUrl(int $projectId, string $state): string
    {
        return self::AUTH_URL.'?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $projectId.':'.$state,
        ]);
    }

    /**
     * @return array{access_token: string, refresh_token: string|null, expires_in: int}
     */
    public function exchangeCode(string $code): array
    {
        $response = $this->http->asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect'),
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Google yetkilendirme başarısız: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Returns a valid access token, refreshing it when it has expired.
     */
    public function accessToken(GscToken $token): string
    {
        if ($token->expires_at && $token->expires_at->isFuture() && filled($token->access_token)) {
            return $token->access_token;
        }

        if (blank($token->refresh_token)) {
            throw new RuntimeException('Search Console bağlantısı yenilenemedi, yeniden yetkilendir.');
        }

        $response = $this->http->asForm()->post(self::TOKEN_URL, [
            'refresh_token' => $token->refresh_token,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Google token yenilenemedi: '.$response->body());
        }

        $token->update([
            'access_token' => $response->json('access_token'),
            'expires_at' => now()->addSeconds((int) $response->json('expires_in', 3600) - 60),
        ]);

        return $token->access_token;
    }

    /**
     * @return list<string>
     */
    public function sites(GscToken $token): array
    {
        $response = $this->http
            ->withToken($this->accessToken($token))
            ->get(self::API_URL.'/sites');

        if ($response->failed()) {
            throw new RuntimeException('Search Console siteleri alınamadı.');
        }

        return collect($response->json('siteEntry', []))
            ->filter(fn ($site) => ($site['permissionLevel'] ?? '') !== 'siteUnverifiedUser')
            ->pluck('siteUrl')
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $dimensions
     * @return list<array<string, mixed>>
     */
    public function query(GscToken $token, string $property, string $start, string $end, array $dimensions, int $rowLimit = 5000): array
    {
        $response = $this->http
            ->withToken($this->accessToken($token))
            ->timeout(90)
            ->post(self::API_URL.'/sites/'.rawurlencode($property).'/searchAnalytics/query', [
                'startDate' => $start,
                'endDate' => $end,
                'dimensions' => $dimensions,
                'rowLimit' => $rowLimit,
                'dataState' => 'final',
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Search Console sorgusu başarısız: '.$response->status());
        }

        return $response->json('rows', []);
    }

    /**
     * The property string Search Console is most likely to hold for a project.
     */
    public function guessProperty(Project $project): string
    {
        return 'sc-domain:'.$project->domain;
    }
}

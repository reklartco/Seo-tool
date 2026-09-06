<?php

namespace App\Services\WordPress;

use App\Models\IntegrationLog;
use App\Models\WpConnection;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

/**
 * Talks to the seo-connector plugin on the customer's WordPress site.
 */
class WordPressClient
{
    public const NAMESPACE = '/wp-json/seoconnector/v1';

    public function __construct(private readonly HttpFactory $http) {}

    /**
     * @return array<string, mixed>
     */
    public function ping(WpConnection $connection, string $apiKey): array
    {
        return $this->send($connection, $apiKey, 'GET', '/ping');
    }

    /**
     * @return array<string, mixed>
     */
    public function updateMeta(WpConnection $connection, string $apiKey, array $payload): array
    {
        return $this->send($connection, $apiKey, 'POST', '/meta', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function updateImageAlt(WpConnection $connection, string $apiKey, array $payload): array
    {
        return $this->send($connection, $apiKey, 'POST', '/image-alt', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function rollback(WpConnection $connection, string $apiKey, int $fixId): array
    {
        return $this->send($connection, $apiKey, 'POST', '/rollback', ['fix_id' => $fixId]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function send(WpConnection $connection, string $apiKey, string $method, string $path, array $payload = []): array
    {
        $body = $payload === [] ? '' : json_encode($payload, JSON_UNESCAPED_UNICODE);
        $timestamp = time();
        $fullPath = self::NAMESPACE.$path;

        $request = $this->http
            ->timeout(30)
            ->withHeaders([
                'X-Seo-Timestamp' => (string) $timestamp,
                'X-Seo-Signature' => Signature::make($apiKey, $method, $fullPath, $timestamp, $body),
                'Content-Type' => 'application/json',
            ]);

        $url = rtrim($connection->site_url, '/').$fullPath;

        $response = $method === 'GET'
            ? $request->get($url)
            : $request->withBody($body ?: '{}', 'application/json')->post($url);

        IntegrationLog::create([
            'project_id' => $connection->project_id,
            'source' => 'wp',
            'action' => trim($path, '/'),
            'payload' => ['status' => $response->status(), 'request' => $payload],
            'success' => $response->successful(),
        ]);

        if ($response->failed()) {
            throw new RuntimeException('WordPress isteği başarısız: HTTP '.$response->status().' — '.$response->body());
        }

        return $response->json() ?? [];
    }
}

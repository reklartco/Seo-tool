<?php

namespace App\Services\Ai;

use App\Models\Page;
use App\Models\Project;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

/**
 * Asks Claude for a title / meta description / alt text rewrite.
 *
 * The prompt follows spec §7: page context in, strict JSON out.
 */
class FixGenerator
{
    public const API_URL = 'https://api.anthropic.com/v1/messages';

    public const VERSION = '2023-06-01';

    public function __construct(private readonly HttpFactory $http) {}

    public function configured(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    /**
     * @param  list<string>  $keywords
     * @return array{title?: string, meta_description?: string, alt?: string, _tokens?: int}
     */
    public function generate(Project $project, Page $page, string $field, array $keywords, ?string $issue = null): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Claude API anahtarı tanımlı değil (.env: ANTHROPIC_API_KEY).');
        }

        $response = $this->http
            ->withHeaders([
                'x-api-key' => config('services.anthropic.key'),
                'anthropic-version' => self::VERSION,
            ])
            ->timeout(90)
            ->retry(2, 1000)
            ->post(self::API_URL, [
                'model' => config('services.anthropic.model'),
                'max_tokens' => 1024,
                'system' => 'Sen bir SEO uzmanısın. Yanıtını SADECE geçerli JSON olarak ver, açıklama ekleme.',
                'messages' => [[
                    'role' => 'user',
                    'content' => $this->prompt($project, $page, $field, $keywords, $issue),
                ]],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Claude isteği başarısız: HTTP '.$response->status());
        }

        $text = collect($response->json('content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        $decoded = $this->decode($text);
        $decoded['_tokens'] = (int) $response->json('usage.input_tokens', 0)
            + (int) $response->json('usage.output_tokens', 0);

        return $decoded;
    }

    /**
     * @param  list<string>  $keywords
     */
    private function prompt(Project $project, Page $page, string $field, array $keywords, ?string $issue): string
    {
        $language = match ($project->language) {
            'en' => 'İngilizce',
            'de' => 'Almanca',
            default => 'Türkçe',
        };

        $brand = str($project->name)->trim()->value() ?: $project->domain;
        $wanted = match ($field) {
            'title' => '{"title": "..."}',
            'meta_description' => '{"meta_description": "..."}',
            'alt' => '{"alt": "..."}',
            default => '{"title": "...", "meta_description": "..."}',
        };

        return <<<PROMPT
        Dil: {$language}.
        Sayfa URL: {$page->url}
        H1: {$page->h1}
        Mevcut title: {$page->title}
        Mevcut description: {$page->meta_description}
        Sorun: {$issue}
        Hedef anahtar kelimeler: {$this->list($keywords)}

        Kurallar:
        - title 50-60 karakter, anahtar kelime başta, marka sonda " | {$brand}".
        - meta_description 140-155 karakter, bir eylem çağrısı içersin.
        - alt metni görseli tarif etsin, 125 karakteri geçmesin.
        - Abartılı vaat, büyük harf blokları ve tırnak işareti kullanma.

        SADECE şu biçimde JSON döndür: {$wanted}
        PROMPT;
    }

    /**
     * @param  list<string>  $keywords
     */
    private function list(array $keywords): string
    {
        return $keywords === [] ? '(belirtilmemiş)' : implode(', ', array_slice($keywords, 0, 10));
    }

    /**
     * @return array<string, string>
     */
    private function decode(string $text): array
    {
        $json = trim($text);

        // Models sometimes wrap the object in a fenced block.
        if (preg_match('/\{.*\}/s', $json, $matches)) {
            $json = $matches[0];
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Claude geçerli JSON döndürmedi.');
        }

        return array_map(fn ($value) => is_string($value) ? trim($value) : $value, $decoded);
    }
}

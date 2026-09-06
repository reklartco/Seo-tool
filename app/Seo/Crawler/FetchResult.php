<?php

namespace App\Seo\Crawler;

class FetchResult
{
    /**
     * @param  array<string, list<string>>  $headers
     */
    public function __construct(
        public readonly string $url,
        public readonly string $finalUrl,
        public readonly int $statusCode,
        public readonly string $body = '',
        public readonly array $headers = [],
        public readonly int $loadTimeMs = 0,
        public readonly int $redirectHops = 0,
        public readonly ?string $error = null,
    ) {}

    public function isHtml(): bool
    {
        return str_contains(mb_strtolower($this->header('Content-Type') ?? ''), 'text/html');
    }

    public function contentType(): ?string
    {
        $type = $this->header('Content-Type');

        return $type ? trim(explode(';', $type)[0]) : null;
    }

    public function size(): int
    {
        return strlen($this->body);
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $values) {
            if (strcasecmp($key, $name) === 0) {
                return is_array($values) ? ($values[0] ?? null) : $values;
            }
        }

        return null;
    }

    public function ok(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}

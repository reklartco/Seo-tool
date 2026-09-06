<?php

namespace App\Seo\Rank;

class SerpResult
{
    /**
     * @param  list<array{position: int, domain: string, url: string, title: string|null}>  $top
     * @param  list<string>  $features
     */
    public function __construct(
        public readonly string $keyword,
        public readonly ?int $rank = null,
        public readonly ?string $url = null,
        public readonly array $top = [],
        public readonly array $features = [],
    ) {}
}

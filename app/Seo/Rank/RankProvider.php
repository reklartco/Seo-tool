<?php

namespace App\Seo\Rank;

interface RankProvider
{
    /**
     * Where the domain ranks for the keyword, plus the top of the SERP.
     */
    public function fetchRank(string $keyword, string $domain, Location $location): SerpResult;

    /**
     * Monthly search volume and cost data.
     *
     * @param  list<string>  $keywords
     * @return array<string, array{search_volume: int|null, cpc: float|null, competition: float|null}>
     */
    public function fetchVolume(array $keywords, Location $location): array;
}

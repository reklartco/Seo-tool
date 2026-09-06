<?php

namespace App\Seo\Rank;

/**
 * Used when no provider credentials are configured: the panel keeps working,
 * keywords simply stay unranked instead of the job blowing up.
 */
class NullRankProvider implements RankProvider
{
    public function fetchRank(string $keyword, string $domain, Location $location): SerpResult
    {
        return new SerpResult(keyword: $keyword);
    }

    public function fetchVolume(array $keywords, Location $location): array
    {
        return [];
    }
}

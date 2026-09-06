<?php

namespace App\Seo;

use App\Models\Crawl;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * The whole-site view a rule needs once every page has been crawled:
 * duplicate detection, sitemap coverage, orphan pages, broken links.
 */
class SiteContext
{
    /**
     * @param  Collection<int, Page>  $pages
     * @param  array{status: int, body: string, blocks_all: bool, sitemaps: list<string>}  $robots
     * @param  array{found: bool, valid: bool, urls: list<string>}  $sitemap
     * @param  list<array{to_url: string, status: int|null, from_page_id: int, is_internal: bool}>  $brokenLinks
     */
    public function __construct(
        public readonly Project $project,
        public readonly Crawl $crawl,
        public readonly Collection $pages,
        public readonly array $robots,
        public readonly array $sitemap,
        public readonly array $brokenLinks = [],
    ) {}

    /**
     * Sitemap URLs as a lookup set.
     *
     * @return array<string, true>
     */
    public function sitemapIndex(): array
    {
        static $index = null;

        return $index ??= array_fill_keys($this->sitemap['urls'] ?? [], true);
    }
}

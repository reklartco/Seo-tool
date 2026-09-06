<?php

namespace App\Seo\SiteRules;

use App\Seo\IssueResult;
use App\Seo\SiteContext;

interface SiteRule
{
    public function key(): string;

    public function category(): string;

    public function severity(): string;

    /**
     * @return list<IssueResult>
     */
    public function check(SiteContext $ctx): array;
}

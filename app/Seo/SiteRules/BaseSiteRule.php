<?php

namespace App\Seo\SiteRules;

use App\Seo\IssueResult;

abstract class BaseSiteRule implements SiteRule
{
    /**
     * @param  array<string, mixed>  $details
     */
    protected function issue(string $message, array $details = [], ?int $pageId = null): IssueResult
    {
        return new IssueResult(
            ruleKey: $this->key(),
            severity: $this->severity(),
            category: $this->category(),
            message: $message,
            details: $details,
            pageId: $pageId,
        );
    }
}

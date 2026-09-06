<?php

namespace App\Seo\Rules;

use App\Seo\IssueResult;
use App\Seo\PageContext;

abstract class BaseRule implements Rule
{
    /**
     * By default a rule only judges pages that actually rendered. Rules about
     * the response itself (status, redirects, transport) override this.
     */
    public function appliesTo(PageContext $ctx): bool
    {
        return $ctx->statusCode >= 200 && $ctx->statusCode < 300;
    }

    /**
     * @param  array<string, mixed>  $details
     */
    protected function issue(string $message, array $details = []): IssueResult
    {
        return new IssueResult(
            ruleKey: $this->key(),
            severity: $this->severity(),
            category: $this->category(),
            message: $message,
            details: $details,
        );
    }
}

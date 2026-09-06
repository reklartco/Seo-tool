<?php

namespace App\Seo\Rules;

use App\Seo\IssueResult;

abstract class BaseRule implements Rule
{
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

<?php

namespace App\Seo;

/**
 * A rule violation found on one page. One rule may report several of these
 * (e.g. one per image missing an alt attribute).
 */
class IssueResult
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly string $ruleKey,
        public readonly string $severity,
        public readonly string $category,
        public readonly string $message,
        public readonly array $details = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rule_key' => $this->ruleKey,
            'severity' => $this->severity,
            'category' => $this->category,
            'message' => $this->message,
            'details' => $this->details,
        ];
    }
}

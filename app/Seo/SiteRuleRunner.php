<?php

namespace App\Seo;

use App\Seo\SiteRules\SiteRule;

/**
 * Runs the whole-site rules after every page of a crawl has settled.
 */
class SiteRuleRunner
{
    /** @var list<SiteRule> */
    private array $rules = [];

    /**
     * @param  iterable<SiteRule>  $rules
     */
    public function __construct(iterable $rules = [])
    {
        foreach ($rules as $rule) {
            $this->rules[] = $rule;
        }
    }

    /**
     * @return list<SiteRule>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * @return list<IssueResult>
     */
    public function run(SiteContext $ctx): array
    {
        $issues = [];

        foreach ($this->rules as $rule) {
            foreach ($rule->check($ctx) as $issue) {
                $issues[] = $issue;
            }
        }

        return $issues;
    }
}

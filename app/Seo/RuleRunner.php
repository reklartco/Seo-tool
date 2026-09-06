<?php

namespace App\Seo;

use App\Seo\Rules\Rule;

/**
 * Runs every registered rule against a page and collects the violations.
 */
class RuleRunner
{
    /** @var list<Rule> */
    private array $rules = [];

    /**
     * @param  iterable<Rule>  $rules
     */
    public function __construct(iterable $rules = [])
    {
        foreach ($rules as $rule) {
            $this->register($rule);
        }
    }

    public function register(Rule $rule): self
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * @return list<Rule>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * @return list<IssueResult>
     */
    public function run(PageContext $ctx): array
    {
        $issues = [];

        foreach ($this->rules as $rule) {
            if (! $rule->appliesTo($ctx)) {
                continue;
            }

            foreach ($rule->check($ctx) as $issue) {
                $issues[] = $issue;
            }
        }

        return $issues;
    }
}

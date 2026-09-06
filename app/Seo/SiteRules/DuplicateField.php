<?php

namespace App\Seo\SiteRules;

use App\Seo\SiteContext;

/**
 * Shared body for the three "same value on several pages" rules.
 */
abstract class DuplicateField extends BaseSiteRule
{
    abstract protected function column(): string;

    abstract protected function label(): string;

    public function check(SiteContext $ctx): array
    {
        $column = $this->column();

        $groups = $ctx->pages
            ->filter(fn ($page) => filled($page->{$column}))
            ->groupBy(fn ($page) => mb_strtolower((string) $page->{$column}))
            ->filter(fn ($group) => $group->count() > 1);

        $issues = [];

        foreach ($groups as $value => $group) {
            foreach ($group as $page) {
                $issues[] = $this->issue(
                    $this->label().' '.$group->count().' sayfada tekrar ediyor.',
                    [
                        'value' => $value,
                        'count' => $group->count(),
                        'pages' => $group->take(10)->pluck('url')->all(),
                        'expected' => 'Her sayfa için benzersiz değer',
                    ],
                    $page->id,
                );
            }
        }

        return $issues;
    }
}

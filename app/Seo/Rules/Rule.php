<?php

namespace App\Seo\Rules;

use App\Seo\IssueResult;
use App\Seo\PageContext;

interface Rule
{
    /** Stable identifier stored on the issue row, e.g. "title_missing". */
    public function key(): string;

    /** meta|content|technical|links|images|schema */
    public function category(): string;

    /** critical|warning|notice */
    public function severity(): string;

    /**
     * Whether this rule has anything to say about this page at all.
     * Content and meta rules stay quiet on error pages.
     */
    public function appliesTo(PageContext $ctx): bool;

    /**
     * Return one or more issues, or an empty array when the page passes.
     *
     * @return list<IssueResult>
     */
    public function check(PageContext $ctx): array;
}

<?php

namespace App\Seo\SiteRules;

class TitleDuplicate extends DuplicateField
{
    public function key(): string
    {
        return 'title_duplicate';
    }

    public function category(): string
    {
        return 'meta';
    }

    public function severity(): string
    {
        return 'warning';
    }

    protected function column(): string
    {
        return 'title';
    }

    protected function label(): string
    {
        return 'Title';
    }
}

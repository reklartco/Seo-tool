<?php

namespace App\Seo\SiteRules;

class MetaDescDuplicate extends DuplicateField
{
    public function key(): string
    {
        return 'meta_desc_duplicate';
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
        return 'meta_description';
    }

    protected function label(): string
    {
        return 'Meta description';
    }
}

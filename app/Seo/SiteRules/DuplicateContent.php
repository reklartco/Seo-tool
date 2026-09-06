<?php

namespace App\Seo\SiteRules;

class DuplicateContent extends DuplicateField
{
    public function key(): string
    {
        return 'duplicate_content';
    }

    public function category(): string
    {
        return 'content';
    }

    public function severity(): string
    {
        return 'critical';
    }

    protected function column(): string
    {
        return 'content_hash';
    }

    protected function label(): string
    {
        return 'Sayfa içeriği';
    }
}

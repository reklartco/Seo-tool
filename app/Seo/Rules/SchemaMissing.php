<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class SchemaMissing extends BaseRule
{
    public function key(): string
    {
        return 'schema_missing';
    }

    public function category(): string
    {
        return 'schema';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function check(PageContext $ctx): array
    {
        $schema = $ctx->schema();

        if (($schema['blocks'] ?? 0) > 0 || ($schema['types'] ?? []) !== []) {
            return [];
        }

        return [$this->issue('Sayfada yapısal veri (schema.org) yok.', [
            'expected' => 'Sayfa türüne uygun JSON-LD (Article, Product, LocalBusiness…)',
        ])];
    }
}

<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class SchemaInvalidJson extends BaseRule
{
    public function key(): string
    {
        return 'schema_invalid_json';
    }

    public function category(): string
    {
        return 'schema';
    }

    public function severity(): string
    {
        return 'critical';
    }

    public function check(PageContext $ctx): array
    {
        $invalid = (int) ($ctx->schema()['invalid'] ?? 0);

        if ($invalid === 0) {
            return [];
        }

        return [$this->issue($invalid.' JSON-LD bloğu geçersiz, Google okuyamıyor.', [
            'invalid_blocks' => $invalid,
            'expected' => 'Geçerli JSON-LD',
        ])];
    }
}

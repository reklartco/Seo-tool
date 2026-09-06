<?php

namespace App\Seo\Rules;

use App\Seo\PageContext;

class ProductSchemaMissing extends BaseRule
{
    public function key(): string
    {
        return 'product_schema_missing';
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
        if ($ctx->cms !== 'woocommerce' || ! $this->looksLikeProduct($ctx)) {
            return [];
        }

        $types = array_map('mb_strtolower', $ctx->schema()['types'] ?? []);

        if (in_array('product', $types, true)) {
            return [];
        }

        return [$this->issue('Ürün sayfasında Product schema yok.', [
            'expected' => 'Fiyat, stok ve değerlendirme içeren Product JSON-LD',
        ])];
    }

    private function looksLikeProduct(PageContext $ctx): bool
    {
        return (bool) preg_match('#/(urun|product|shop)/#i', $ctx->url);
    }
}

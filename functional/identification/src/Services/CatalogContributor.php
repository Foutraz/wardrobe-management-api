<?php

namespace Functional\Identification\Services;

use Functional\Catalog\Enums\ProductSource;
use Functional\Catalog\Models\Product;
use Functional\Catalog\Models\ProductVariant;

class CatalogContributor
{
    /**
     * File what an owner confirmed into the shared catalogue, unverified until a moderator sees it.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function contribute(array $attributes): ?ProductVariant
    {
        $name = $attributes['name'] ?? null;
        $categoryId = $attributes['category_id'] ?? null;
        $barcode = $attributes['barcode'] ?? null;

        if (! is_string($name) || ! is_string($categoryId)) {
            return null;
        }

        if (is_string($barcode) && $barcode !== '') {
            $known = ProductVariant::query()->where('ean', $barcode)->first();

            if ($known !== null) {
                return $known;
            }
        }

        $product = new Product;

        $product->forceFill([
            'brand_id' => $attributes['brand_id'] ?? null,
            'category_id' => $categoryId,
            'name' => $name,
            'material_composition' => $attributes['material_composition'] ?? null,
            'style_reference' => $attributes['style_reference'] ?? null,
            'source' => ProductSource::UserContributed,
            'verified_at' => null,
        ])->save();

        $variant = new ProductVariant;

        $variant->forceFill([
            'product_id' => $product->getKey(),
            'size_label' => $attributes['size_label'] ?? 'unknown',
            'colour_name' => $attributes['colour_name'] ?? null,
            'colour_hex' => $attributes['colour_hex'] ?? null,
            'ean' => is_string($barcode) && $barcode !== '' ? $barcode : null,
        ])->save();

        return $variant;
    }
}

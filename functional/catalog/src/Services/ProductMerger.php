<?php

namespace Functional\Catalog\Services;

use Functional\Catalog\Events\ProductVariantMerged;
use Functional\Catalog\Exceptions\ProductsCannotBeMerged;
use Functional\Catalog\Models\Product;
use Functional\Catalog\Models\ProductVariant;

class ProductMerger
{
    /**
     * Fold a duplicate catalogue entry into the one worth keeping.
     *
     * @throws ProductsCannotBeMerged
     */
    public function merge(Product $duplicate, Product $survivor): Product
    {
        if ($duplicate->is($survivor)) {
            throw ProductsCannotBeMerged::intoItself();
        }

        if ($duplicate->isVerified() && ! $survivor->isVerified()) {
            throw ProductsCannotBeMerged::becauseTheSurvivorIsUnverified();
        }

        $duplicate->variants()->cursor()->each(
            fn (ProductVariant $variant) => $this->moveVariant($variant, $survivor),
        );

        $duplicate->delete();

        return $survivor->fresh() ?? $survivor;
    }

    /**
     * Move a variant across, or retire it when the survivor already has its twin.
     */
    private function moveVariant(ProductVariant $variant, Product $survivor): void
    {
        $twin = $survivor->variants()
            ->where('size_label', $variant->size_label)
            ->where('colour_name', $variant->colour_name)
            ->first();

        if ($twin === null) {
            $variant->forceFill(['product_id' => $survivor->getKey()])->save();

            return;
        }

        $this->carryOverBarcode($variant, $twin);

        ProductVariantMerged::dispatch($variant->getKey(), $twin->getKey());

        $variant->delete();
    }

    /**
     * Hand a barcode to the surviving twin when it has none, freeing the unique index first.
     */
    private function carryOverBarcode(ProductVariant $variant, ProductVariant $twin): void
    {
        if ($twin->ean !== null || $variant->ean === null) {
            return;
        }

        $barcode = $variant->ean;

        $variant->forceFill(['ean' => null])->save();
        $twin->forceFill(['ean' => $barcode])->save();
    }
}

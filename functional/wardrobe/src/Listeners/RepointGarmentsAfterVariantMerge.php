<?php

namespace Functional\Wardrobe\Listeners;

use Functional\Catalog\Events\ProductVariantMerged;
use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WishlistItem;

class RepointGarmentsAfterVariantMerge
{
    /**
     * Follow a merged catalogue variant so no wardrobe entry is left pointing at a retired one.
     */
    public function handle(ProductVariantMerged $event): void
    {
        Garment::withTrashed()
            ->where('product_variant_id', $event->mergedVariantId)
            ->cursor()
            ->each(fn (Garment $garment) => $garment
                ->forceFill(['product_variant_id' => $event->survivingVariantId])
                ->save());

        WishlistItem::query()
            ->where('product_variant_id', $event->mergedVariantId)
            ->cursor()
            ->each(fn (WishlistItem $wishlistItem) => $wishlistItem
                ->forceFill(['product_variant_id' => $event->survivingVariantId])
                ->save());
    }
}

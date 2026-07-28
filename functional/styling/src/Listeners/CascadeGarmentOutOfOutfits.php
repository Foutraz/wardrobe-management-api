<?php

namespace Functional\Styling\Listeners;

use Functional\Styling\Models\OutfitItem;
use Functional\Wardrobe\Models\Garment;

class CascadeGarmentOutOfOutfits
{
    /**
     * Pull a garment out of every outfit before it is erased, so its foreign key cannot block the delete.
     */
    public function handle(Garment $garment): void
    {
        if (! $garment->isForceDeleting()) {
            return;
        }

        OutfitItem::query()
            ->where('garment_id', $garment->id)
            ->cursor()
            ->each(fn (OutfitItem $outfitItem) => $outfitItem->delete());
    }
}

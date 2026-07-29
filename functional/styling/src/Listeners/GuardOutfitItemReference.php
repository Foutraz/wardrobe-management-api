<?php

namespace Functional\Styling\Listeners;

use Functional\Styling\Exceptions\OutfitItemMustReferenceExactlyOneThing;
use Functional\Styling\Models\OutfitItem;

class GuardOutfitItemReference
{
    /**
     * Hold the exactly-one-reference invariant in code, since SQLite cannot carry it as a CHECK.
     *
     * @throws OutfitItemMustReferenceExactlyOneThing
     */
    public function handle(OutfitItem $outfitItem): void
    {
        $references = array_filter([
            $outfitItem->getAttribute('garment_id'),
            $outfitItem->getAttribute('wishlist_item_id'),
        ]);

        if (count($references) !== 1) {
            throw OutfitItemMustReferenceExactlyOneThing::make();
        }
    }
}

<?php

namespace Functional\Styling\Listeners;

use Functional\Styling\Models\OutfitItem;
use Functional\Wardrobe\Models\WishlistItem;

class CascadeWishlistItemOutOfOutfits
{
    /**
     * Pull a wished-for thing out of every outfit before it is erased, so its foreign key cannot block the delete.
     */
    public function handle(WishlistItem $wishlistItem): void
    {
        OutfitItem::query()
            ->where('wishlist_item_id', $wishlistItem->id)
            ->cursor()
            ->each(fn (OutfitItem $outfitItem) => $outfitItem->delete());
    }
}

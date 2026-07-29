<?php

namespace Functional\Wardrobe\Listeners;

use Functional\Users\Models\User;
use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WishlistItem;

class CascadeUserDeletion
{
    /**
     * Remove the wardrobe belonging to a departing account, mirroring how the account itself went.
     */
    public function handle(User $user): void
    {
        $isForceDeleting = $user->isForceDeleting();

        Garment::withTrashed()
            ->where('user_id', $user->id)
            ->cursor()
            ->each(fn (Garment $garment) => $isForceDeleting
                ? $garment->forceDelete()
                : $garment->delete());

        WishlistItem::query()
            ->where('user_id', $user->id)
            ->cursor()
            ->each(fn (WishlistItem $wishlistItem) => $wishlistItem->delete());
    }
}

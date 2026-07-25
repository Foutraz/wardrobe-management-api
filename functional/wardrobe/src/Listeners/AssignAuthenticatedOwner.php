<?php

namespace Functional\Wardrobe\Listeners;

use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WishlistItem;
use Illuminate\Support\Facades\Auth;

class AssignAuthenticatedOwner
{
    /**
     * Attach a new wardrobe record to whoever is signed in, so ownership never travels in the request body.
     */
    public function handle(Garment|WishlistItem $ownedRecord): void
    {
        if ($ownedRecord->getAttribute('user_id') !== null || ! Auth::hasUser()) {
            return;
        }

        $ownedRecord->setAttribute('user_id', Auth::id());
    }
}

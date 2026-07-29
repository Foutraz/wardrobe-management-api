<?php

namespace Functional\Styling\Listeners;

use Functional\Styling\Models\Avatar;
use Functional\Styling\Models\Outfit;
use Functional\Styling\Models\OutfitPlan;
use Illuminate\Support\Facades\Auth;

class AssignAuthenticatedOwner
{
    /**
     * Attach a new styling record to whoever is signed in, so ownership never travels in the request body.
     */
    public function handle(Avatar|Outfit|OutfitPlan $ownedRecord): void
    {
        if ($ownedRecord->getAttribute('user_id') !== null || ! Auth::hasUser()) {
            return;
        }

        $ownedRecord->setAttribute('user_id', Auth::id());
    }
}

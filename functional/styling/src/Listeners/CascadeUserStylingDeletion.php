<?php

namespace Functional\Styling\Listeners;

use Functional\Styling\Models\Avatar;
use Functional\Styling\Models\Outfit;
use Functional\Styling\Models\OutfitPlan;
use Functional\Users\Models\User;

class CascadeUserStylingDeletion
{
    /**
     * Remove the outfits and avatars of a departing account, mirroring how the account itself went.
     */
    public function handle(User $user): void
    {
        $isForceDeleting = $user->isForceDeleting();

        OutfitPlan::query()
            ->where('user_id', $user->id)
            ->cursor()
            ->each(fn (OutfitPlan $outfitPlan) => $outfitPlan->delete());

        Outfit::withTrashed()
            ->where('user_id', $user->id)
            ->cursor()
            ->each(fn (Outfit $outfit) => $isForceDeleting
                ? $outfit->forceDelete()
                : $outfit->delete());

        Avatar::query()
            ->where('user_id', $user->id)
            ->cursor()
            ->each(fn (Avatar $avatar) => $avatar->delete());
    }
}

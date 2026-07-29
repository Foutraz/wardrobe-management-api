<?php

namespace Functional\Styling\Listeners;

use Functional\Styling\Models\Outfit;
use Functional\Styling\Models\OutfitItem;
use Functional\Styling\Models\OutfitPlan;
use Functional\Styling\Models\OutfitPreview;

class CascadeOutfitDeletion
{
    /**
     * Keep an outfit's composition intact while it is only in the bin, so a restore brings it back whole.
     */
    public function handle(Outfit $outfit): void
    {
        if (! $outfit->isForceDeleting()) {
            return;
        }

        OutfitItem::query()
            ->where('outfit_id', $outfit->id)
            ->cursor()
            ->each(fn (OutfitItem $outfitItem) => $outfitItem->delete());

        OutfitPreview::query()
            ->where('outfit_id', $outfit->id)
            ->cursor()
            ->each(fn (OutfitPreview $outfitPreview) => $outfitPreview->delete());

        OutfitPlan::query()
            ->where('outfit_id', $outfit->id)
            ->cursor()
            ->each(fn (OutfitPlan $outfitPlan) => $outfitPlan->delete());
    }
}

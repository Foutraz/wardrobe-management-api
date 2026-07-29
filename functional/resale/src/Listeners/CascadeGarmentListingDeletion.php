<?php

namespace Functional\Resale\Listeners;

use Functional\Resale\Models\VintedListingDraft;
use Functional\Wardrobe\Models\Garment;

class CascadeGarmentListingDeletion
{
    /**
     * Discard the drafts of an erased garment, so its foreign key cannot block the delete.
     */
    public function handle(Garment $garment): void
    {
        if (! $garment->isForceDeleting()) {
            return;
        }

        VintedListingDraft::query()
            ->where('garment_id', $garment->id)
            ->cursor()
            ->each(fn (VintedListingDraft $draft) => $draft->delete());
    }
}

<?php

namespace Functional\Wardrobe\Listeners;

use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WearEvent;

class CascadeGarmentDeletion
{
    /**
     * Discard the wear history only once the garment is gone for good, so a restore keeps its cost per wear.
     */
    public function handle(Garment $garment): void
    {
        if (! $garment->isForceDeleting()) {
            return;
        }

        WearEvent::query()
            ->where('garment_id', $garment->id)
            ->cursor()
            ->each(fn (WearEvent $wearEvent) => $wearEvent->delete());
    }
}

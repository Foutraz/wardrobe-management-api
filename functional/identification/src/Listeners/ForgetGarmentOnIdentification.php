<?php

namespace Functional\Identification\Listeners;

use Functional\Identification\Models\IdentificationRequest;
use Functional\Wardrobe\Models\Garment;

class ForgetGarmentOnIdentification
{
    /**
     * Let go of an erased garment while keeping the identification that produced it.
     */
    public function handle(Garment $garment): void
    {
        if (! $garment->isForceDeleting()) {
            return;
        }

        IdentificationRequest::query()
            ->where('created_garment_id', $garment->id)
            ->cursor()
            ->each(fn (IdentificationRequest $request) => $request
                ->forceFill(['created_garment_id' => null])
                ->save());
    }
}

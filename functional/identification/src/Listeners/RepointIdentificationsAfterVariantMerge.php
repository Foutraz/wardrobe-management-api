<?php

namespace Functional\Identification\Listeners;

use Functional\Catalog\Events\ProductVariantMerged;
use Functional\Identification\Models\IdentificationRequest;

class RepointIdentificationsAfterVariantMerge
{
    /**
     * Follow a merged catalogue variant so no past identification points at a retired one.
     */
    public function handle(ProductVariantMerged $event): void
    {
        IdentificationRequest::query()
            ->where('resolved_product_variant_id', $event->mergedVariantId)
            ->cursor()
            ->each(fn (IdentificationRequest $request) => $request
                ->forceFill(['resolved_product_variant_id' => $event->survivingVariantId])
                ->save());
    }
}

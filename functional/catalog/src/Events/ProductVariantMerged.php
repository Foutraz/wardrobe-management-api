<?php

namespace Functional\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ProductVariantMerged
{
    use Dispatchable;

    /**
     * Announce that one variant has been folded into another, so holders of the old
     * identifier can move their references without the catalogue knowing who they are.
     */
    public function __construct(
        public readonly string $mergedVariantId,
        public readonly string $survivingVariantId,
    ) {}
}

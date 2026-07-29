<?php

namespace Functional\Wardrobe\Values;

final readonly class CostPerWear
{
    public function __construct(
        private ?int $purchasePriceCents,
        private int $wearCount,
    ) {}

    /**
     * Determine whether enough is known about the garment to compute a cost per wear.
     */
    public function isKnown(): bool
    {
        return $this->purchasePriceCents !== null && $this->wearCount > 0;
    }

    /**
     * Get the cost of a single wear in cents, or null while it cannot be computed.
     */
    public function cents(): ?int
    {
        if (! $this->isKnown()) {
            return null;
        }

        return (int) round($this->purchasePriceCents / $this->wearCount);
    }
}

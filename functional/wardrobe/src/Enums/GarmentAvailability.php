<?php

namespace Functional\Wardrobe\Enums;

enum GarmentAvailability: string
{
    case Available = 'available';
    case Dirty = 'dirty';
    case InLaundry = 'in_laundry';
    case Drying = 'drying';
    case NeedsRepair = 'needs_repair';
    case Stored = 'stored';
    case Lent = 'lent';

    /**
     * Determine whether the garment can be worn right now.
     */
    public function isAvailable(): bool
    {
        return $this === self::Available;
    }

    /**
     * Determine whether the garment is somewhere in the laundry cycle.
     */
    public function isInLaundryCycle(): bool
    {
        return in_array($this, [self::Dirty, self::InLaundry, self::Drying], true);
    }

    /**
     * Determine whether the garment may be listed for resale in this state.
     */
    public function isSellable(): bool
    {
        return in_array($this, [self::Available, self::Stored], true);
    }

    /**
     * Get the translated label for this state.
     */
    public function label(): string
    {
        return __('wardrobe::availability.'.$this->value);
    }
}

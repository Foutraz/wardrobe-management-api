<?php

namespace Functional\Styling\Enums;

enum OutfitSlot: string
{
    case Head = 'head';
    case Outer = 'outer';
    case Top = 'top';
    case Bottom = 'bottom';
    case Feet = 'feet';
    case Accessory = 'accessory';

    /**
     * Get the order slots are stacked in on a flat lay, from top to bottom.
     */
    public function layoutOrder(): int
    {
        return match ($this) {
            self::Head => 0,
            self::Outer => 1,
            self::Top => 2,
            self::Bottom => 3,
            self::Feet => 4,
            self::Accessory => 5,
        };
    }

    /**
     * Determine whether more than one garment may occupy this slot.
     */
    public function acceptsSeveral(): bool
    {
        return $this === self::Accessory;
    }

    /**
     * Get the translated label for this slot.
     */
    public function label(): string
    {
        return __('styling::slot.'.$this->value);
    }
}

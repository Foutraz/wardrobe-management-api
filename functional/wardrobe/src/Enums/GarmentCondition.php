<?php

namespace Functional\Wardrobe\Enums;

enum GarmentCondition: string
{
    case NewWithTag = 'new_with_tag';
    case NewWithoutTag = 'new_without_tag';
    case VeryGood = 'very_good';
    case Good = 'good';
    case Satisfactory = 'satisfactory';

    /**
     * Determine whether the garment has never been worn.
     */
    public function isUnworn(): bool
    {
        return in_array($this, [self::NewWithTag, self::NewWithoutTag], true);
    }

    /**
     * Get the translated label for this condition.
     */
    public function label(): string
    {
        return __('wardrobe::condition.'.$this->value);
    }
}

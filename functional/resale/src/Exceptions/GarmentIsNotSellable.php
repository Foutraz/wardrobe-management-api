<?php

namespace Functional\Resale\Exceptions;

use Functional\Wardrobe\Enums\GarmentAvailability;
use RuntimeException;

class GarmentIsNotSellable extends RuntimeException
{
    /**
     * Report a garment whose current state rules out putting it up for sale.
     */
    public static function because(GarmentAvailability $availability): self
    {
        return new self(sprintf(
            'A garment that is %s cannot be listed for sale.',
            $availability->value,
        ));
    }
}

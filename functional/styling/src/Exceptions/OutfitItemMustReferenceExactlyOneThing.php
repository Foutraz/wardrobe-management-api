<?php

namespace Functional\Styling\Exceptions;

use RuntimeException;

class OutfitItemMustReferenceExactlyOneThing extends RuntimeException
{
    /**
     * Report an outfit item that points at neither a garment nor a wished-for item, or at both.
     */
    public static function make(): self
    {
        return new self(
            'An outfit item must reference exactly one of a garment or a wishlist item.',
        );
    }
}

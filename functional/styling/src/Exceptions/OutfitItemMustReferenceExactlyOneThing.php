<?php

namespace Functional\Styling\Exceptions;

use Illuminate\Http\JsonResponse;
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

    /**
     * Answer with an unprocessable status, since the request was understood but the item is malformed.
     */
    public function render(): JsonResponse
    {
        return new JsonResponse(
            ['message' => $this->getMessage()],
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}

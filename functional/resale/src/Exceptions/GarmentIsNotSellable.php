<?php

namespace Functional\Resale\Exceptions;

use Functional\Wardrobe\Enums\GarmentAvailability;
use Illuminate\Http\JsonResponse;
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

    /**
     * Answer with an unprocessable status, since the request was understood but the garment is not ready.
     */
    public function render(): JsonResponse
    {
        return new JsonResponse(
            ['message' => $this->getMessage()],
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}

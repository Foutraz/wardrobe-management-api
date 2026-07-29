<?php

namespace Functional\Wardrobe\Http\Controllers;

use Functional\Wardrobe\Enums\GarmentAvailability;
use Functional\Wardrobe\Http\Requests\UpdateGarmentAvailabilityRequest;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class UpdateGarmentAvailabilityController
{
    /**
     * Move the garment between the laundry basket, the wardrobe and the other states it can sit in.
     */
    public function __invoke(UpdateGarmentAvailabilityRequest $request, Garment $garment): JsonResponse
    {
        Gate::authorize('update', $garment);

        $status = $request->enum('availability_status', GarmentAvailability::class);

        $garment->update(['availability_status' => $status]);

        return new JsonResponse([
            'data' => [
                'id' => $garment->getKey(),
                'availability_status' => $status->value,
                'label' => $status->label(),
                'is_available' => $status->isAvailable(),
            ],
        ]);
    }
}

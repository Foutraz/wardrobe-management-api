<?php

namespace Functional\Wardrobe\Http\Controllers;

use Functional\Wardrobe\Http\Requests\MarkGarmentWornRequest;
use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WearEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class MarkGarmentWornController
{
    /**
     * Record that the garment was worn, which is what makes its cost per wear computable.
     */
    public function __invoke(MarkGarmentWornRequest $request, Garment $garment): JsonResponse
    {
        Gate::authorize('update', $garment);

        $wearEvent = WearEvent::create([
            'garment_id' => $garment->getKey(),
            'worn_on' => $request->date('worn_on') ?? now(),
        ]);

        $garment->loadCount('wearEvents');

        return new JsonResponse([
            'data' => [
                'wear_event_id' => $wearEvent->getKey(),
                'worn_on' => $wearEvent->worn_on->toDateString(),
                'wear_count' => $garment->wear_events_count,
                'cost_per_wear_cents' => $garment->costPerWear()->cents(),
            ],
        ], JsonResponse::HTTP_CREATED);
    }
}

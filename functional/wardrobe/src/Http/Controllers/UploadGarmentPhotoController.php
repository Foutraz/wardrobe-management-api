<?php

namespace Functional\Wardrobe\Http\Controllers;

use Functional\Wardrobe\Enums\GarmentMediaCollection;
use Functional\Wardrobe\Http\Requests\UploadGarmentPhotoRequest;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Technical\Media\Services\CutoutPipeline;

class UploadGarmentPhotoController
{
    /**
     * Store a photo of the garment and immediately attempt a background-free copy of it.
     */
    public function __invoke(
        UploadGarmentPhotoRequest $request,
        Garment $garment,
        CutoutPipeline $cutoutPipeline,
    ): JsonResponse {
        Gate::authorize('update', $garment);

        $photo = $garment->addMediaFromRequest('photo')
            ->toMediaCollection(GarmentMediaCollection::Photos->value);

        $outcome = $cutoutPipeline->run(
            $garment,
            $photo,
            GarmentMediaCollection::Cutouts->value,
            $request->user()?->getAuthIdentifier(),
        );

        return new JsonResponse([
            'data' => [
                'photo_id' => $photo->getKey(),
                'cutout_status' => $outcome->status->value,
                'cutout_failure_reason' => $outcome->failureReason,
            ],
        ], JsonResponse::HTTP_CREATED);
    }
}

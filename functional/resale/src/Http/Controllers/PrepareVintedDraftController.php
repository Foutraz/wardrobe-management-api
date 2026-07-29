<?php

namespace Functional\Resale\Http\Controllers;

use Functional\Resale\Services\ListingDraftComposer;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PrepareVintedDraftController
{
    /**
     * Compose a Vinted listing from what we already know about the garment.
     */
    public function __invoke(Garment $garment, ListingDraftComposer $composer): JsonResponse
    {
        Gate::authorize('update', $garment);

        $draft = $composer->compose($garment);

        return new JsonResponse([
            'data' => [
                'id' => $draft->getKey(),
                'title' => $draft->title,
                'description' => $draft->description,
                'brand_label' => $draft->brand_label,
                'size_label' => $draft->size_label,
                'colour_label' => $draft->colour_label,
                'condition_label' => $draft->condition_label,
                'price_cents' => $draft->price_cents,
                'currency' => $draft->currency,
                'status' => $draft->status->value,
                'status_label' => $draft->status->label(),
            ],
        ], JsonResponse::HTTP_CREATED);
    }
}

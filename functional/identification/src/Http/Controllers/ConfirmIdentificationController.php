<?php

namespace Functional\Identification\Http\Controllers;

use Functional\Identification\Enums\IdentificationStatus;
use Functional\Identification\Exceptions\IdentificationIsNotConfirmable;
use Functional\Identification\Http\Requests\ConfirmIdentificationRequest;
use Functional\Identification\Models\IdentificationRequest;
use Functional\Identification\Services\CatalogContributor;
use Functional\Identification\Services\GarmentBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ConfirmIdentificationController
{
    /**
     * Turn a reviewed identification into a real garment, which is the only path that creates one.
     *
     * @throws IdentificationIsNotConfirmable
     */
    public function __invoke(
        ConfirmIdentificationRequest $request,
        IdentificationRequest $identificationRequest,
        CatalogContributor $contributor,
        GarmentBuilder $builder,
    ): JsonResponse {
        Gate::authorize('update', $identificationRequest);

        if (! $identificationRequest->status->awaitsConfirmation()) {
            throw IdentificationIsNotConfirmable::because($identificationRequest->status);
        }

        $confirmed = [
            ...$identificationRequest->extracted_attributes,
            ...$request->validated(),
            'barcode' => $identificationRequest->barcode,
        ];

        $wasResolved = $identificationRequest->resolved_product_variant_id !== null;

        $variant = $wasResolved
            ? $identificationRequest->resolvedProductVariant
            : $contributor->contribute($confirmed);

        $garment = $builder->build($identificationRequest->user_id, $confirmed, $variant);

        $identificationRequest->transitionTo(IdentificationStatus::Confirmed, [
            'created_garment_id' => $garment->getKey(),
            'confirmed_at' => now(),
        ]);

        return new JsonResponse([
            'data' => [
                'identification_request_id' => $identificationRequest->getKey(),
                'garment_id' => $garment->getKey(),
                'product_variant_id' => $variant?->getKey(),
                'contributed_to_catalogue' => $variant !== null && ! $wasResolved,
            ],
        ], JsonResponse::HTTP_CREATED);
    }
}

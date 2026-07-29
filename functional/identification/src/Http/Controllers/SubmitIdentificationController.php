<?php

namespace Functional\Identification\Http\Controllers;

use Functional\Identification\Enums\IdentificationKind;
use Functional\Identification\Enums\IdentificationStatus;
use Functional\Identification\Http\Requests\SubmitIdentificationRequest;
use Functional\Identification\Models\IdentificationRequest;
use Functional\Identification\Services\IdentificationPipeline;
use Illuminate\Http\JsonResponse;

class SubmitIdentificationController
{
    /**
     * Submit a barcode or an image for identification and run it through the pipeline.
     */
    public function __invoke(
        SubmitIdentificationRequest $request,
        IdentificationPipeline $pipeline,
    ): JsonResponse {
        $kind = $request->enum('kind', IdentificationKind::class);

        $identificationRequest = new IdentificationRequest;

        $identificationRequest->forceFill([
            'user_id' => $request->user()?->getAuthIdentifier(),
            'kind' => $kind,
            'status' => IdentificationStatus::Pending,
            'barcode' => $kind === IdentificationKind::Barcode ? $request->string('barcode')->toString() : null,
            'extracted_attributes' => [],
        ])->save();

        if ($kind->needsImage()) {
            $identificationRequest->addMediaFromRequest('image')
                ->toMediaCollection(IdentificationRequest::SUBJECT_COLLECTION);

            $identificationRequest->refresh();
        }

        $resolved = $pipeline->run($identificationRequest);

        return new JsonResponse([
            'data' => [
                'id' => $resolved->getKey(),
                'kind' => $resolved->kind->value,
                'kind_label' => $resolved->kind->label(),
                'status' => $resolved->status->value,
                'awaits_confirmation' => $resolved->status->awaitsConfirmation(),
                'confidence' => $resolved->confidence,
                'provider' => $resolved->provider,
                'extracted_attributes' => $resolved->extracted_attributes,
                'failure_reason' => $resolved->failure_reason,
            ],
        ], JsonResponse::HTTP_CREATED);
    }
}

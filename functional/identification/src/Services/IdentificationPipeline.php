<?php

namespace Functional\Identification\Services;

use Functional\Identification\Enums\IdentificationKind;
use Functional\Identification\Enums\IdentificationStatus;
use Functional\Identification\Models\IdentificationRequest;
use Technical\AiGateway\Contracts\CareLabelReader;
use Technical\AiGateway\Enums\AiOperationKind;
use Technical\AiGateway\Services\AiOperationJournal;
use Technical\AiGateway\Values\AttributeReading;
use Technical\Media\Services\MediaScratchFile;

class IdentificationPipeline
{
    public function __construct(
        private readonly BarcodeResolver $barcodeResolver,
        private readonly CareLabelReader $careLabelReader,
        private readonly AiOperationJournal $journal,
        private readonly MediaScratchFile $scratch,
    ) {}

    /**
     * Work the request through whichever layer suits it, leaving the owner to confirm the result.
     */
    public function run(IdentificationRequest $request): IdentificationRequest
    {
        $request->transitionTo(IdentificationStatus::Processing);

        $startedAt = hrtime(true);
        $reading = $this->read($request);
        $latencyMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);

        $this->journal->record(
            $this->journalKindFor($request->kind),
            $this->providerFor($request->kind),
            $reading->toOutcome(),
            $latencyMs,
            $request,
            $request->user_id,
        );

        if (! $reading->status->producedOutput()) {
            $request->transitionTo(IdentificationStatus::Failed, [
                'failure_reason' => $reading->failureReason,
                'provider' => $this->providerFor($request->kind),
            ]);

            return $request;
        }

        $request->transitionTo(IdentificationStatus::Succeeded, [
            'extracted_attributes' => $reading->attributes,
            'confidence' => $reading->confidence,
            'provider' => $this->providerFor($request->kind),
            'resolved_product_variant_id' => $request->kind->canResolveVariant() && $request->barcode !== null
                ? $this->barcodeResolver->variantFor($request->barcode)?->getKey()
                : null,
        ]);

        return $request;
    }

    /**
     * Pick the layer that matches what the owner submitted.
     */
    private function read(IdentificationRequest $request): AttributeReading
    {
        if ($request->kind === IdentificationKind::Barcode) {
            return $request->barcode === null
                ? AttributeReading::failed('No barcode was submitted.')
                : $this->barcodeResolver->resolve($request->barcode);
        }

        $image = $request->getFirstMedia(IdentificationRequest::SUBJECT_COLLECTION);

        if ($image === null) {
            return AttributeReading::failed('No image was submitted.');
        }

        if ($request->kind === IdentificationKind::CareLabel) {
            if (! $this->careLabelReader->isAvailable()) {
                return AttributeReading::unavailable('No care label reader is available on this machine.');
            }

            // getPath() is an object key on S3, so the bytes are copied locally first.
            $scratchPath = $this->scratch->materialise($image);
            $reading = $this->careLabelReader->read($scratchPath);
            $this->scratch->release($scratchPath);

            return $reading;
        }

        return AttributeReading::unavailable('No garment vision reader is available on this machine.');
    }

    /**
     * Map the submitted kind onto the operation the journal records it under.
     */
    private function journalKindFor(IdentificationKind $kind): AiOperationKind
    {
        return match ($kind) {
            IdentificationKind::CareLabel => AiOperationKind::CareLabelOcr,
            default => AiOperationKind::AttributeExtraction,
        };
    }

    /**
     * Name the provider that handled the kind, so a failure can be traced to it.
     */
    private function providerFor(IdentificationKind $kind): string
    {
        return match ($kind) {
            IdentificationKind::Barcode => $this->barcodeResolver->name(),
            IdentificationKind::CareLabel => $this->careLabelReader->name(),
            IdentificationKind::Photo => 'none',
        };
    }
}

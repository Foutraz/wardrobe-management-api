<?php

namespace Functional\Resale\Http\Controllers;

use Functional\Resale\Contracts\ListingPublisher;
use Functional\Resale\Enums\VintedDraftStatus;
use Functional\Resale\Http\Requests\TransitionVintedDraftRequest;
use Functional\Resale\Models\VintedListingDraft;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class TransitionVintedDraftController
{
    /**
     * Walk the draft along its lifecycle, handing it to the seller when it reaches that step.
     */
    public function __invoke(
        TransitionVintedDraftRequest $request,
        VintedListingDraft $draft,
        ListingPublisher $publisher,
    ): JsonResponse {
        Gate::authorize('update', $draft);

        $status = $request->enum('status', VintedDraftStatus::class);

        $draft->transitionTo($status);

        $instructions = $status === VintedDraftStatus::HandedOff
            ? $publisher->publish($draft)
            : null;

        return new JsonResponse([
            'data' => [
                'id' => $draft->getKey(),
                'status' => $draft->status->value,
                'status_label' => $draft->status->label(),
                'handed_off_at' => $draft->handed_off_at?->toIso8601String(),
                'handoff' => $instructions === null ? null : [
                    'publisher' => $instructions->publisher,
                    'target' => $instructions->target,
                    'photo_urls' => $instructions->photoUrls,
                    'guidance' => $instructions->guidance,
                ],
            ],
        ]);
    }
}

<?php

namespace Functional\Styling\Http\Controllers;

use Functional\Styling\Enums\StylingMediaCollection;
use Functional\Styling\Models\Outfit;
use Functional\Styling\Services\FlatLayRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RenderOutfitPreviewController
{
    /**
     * Return the outfit's flat lay, composing it only when no cached render already exists.
     */
    public function __invoke(Outfit $outfit, FlatLayRenderer $renderer): JsonResponse
    {
        Gate::authorize('view', $outfit);

        $preview = $renderer->render($outfit);

        return new JsonResponse([
            'data' => [
                'id' => $preview->getKey(),
                'mode' => $preview->mode->value,
                'status' => $preview->status->value,
                'failure_reason' => $preview->failure_reason,
                'image_url' => $preview->status->hasImage()
                    ? $preview->getFirstMediaUrl(StylingMediaCollection::Render->value)
                    : null,
            ],
        ], $preview->status->hasImage() ? JsonResponse::HTTP_OK : JsonResponse::HTTP_ACCEPTED);
    }
}

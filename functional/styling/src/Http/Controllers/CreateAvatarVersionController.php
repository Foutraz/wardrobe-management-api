<?php

namespace Functional\Styling\Http\Controllers;

use Functional\Styling\Enums\StylingMediaCollection;
use Functional\Styling\Http\Requests\CreateAvatarVersionRequest;
use Functional\Styling\Models\Avatar;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Technical\Media\Services\SignedMediaUrl;

class CreateAvatarVersionController
{
    /**
     * Freeze a new canonical body image, which every later preview renders against.
     */
    public function __invoke(
        CreateAvatarVersionRequest $request,
        Avatar $avatar,
        SignedMediaUrl $signer,
    ): JsonResponse {
        Gate::authorize('update', $avatar);

        $version = $avatar->versions()->create([
            'version' => $avatar->nextVersionNumber(),
            'parameters' => $request->array('parameters'),
        ]);

        $version->addMediaFromRequest('image')
            ->toMediaCollection(StylingMediaCollection::CanonicalImage->value);

        return new JsonResponse([
            'data' => [
                'id' => $version->getKey(),
                'version' => $version->version,
                'has_canonical_image' => $version->fresh()->hasCanonicalImage(),
                'image_url' => $signer->for($version->getFirstMedia(StylingMediaCollection::CanonicalImage->value)),
            ],
        ], JsonResponse::HTTP_CREATED);
    }
}

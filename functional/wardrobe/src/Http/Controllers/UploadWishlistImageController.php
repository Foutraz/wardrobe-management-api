<?php

namespace Functional\Wardrobe\Http\Controllers;

use Functional\Wardrobe\Enums\WishlistMediaCollection;
use Functional\Wardrobe\Http\Requests\UploadWishlistImageRequest;
use Functional\Wardrobe\Models\WishlistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Technical\Media\Services\SignedMediaUrl;

class UploadWishlistImageController
{
    /**
     * Cache the product shot of something the owner is considering, so it can join a flat lay.
     */
    public function __invoke(
        UploadWishlistImageRequest $request,
        WishlistItem $wishlistItem,
        SignedMediaUrl $signer,
    ): JsonResponse {
        Gate::authorize('update', $wishlistItem);

        $image = $wishlistItem->addMediaFromRequest('image')
            ->toMediaCollection(WishlistMediaCollection::CachedImage->value);

        return new JsonResponse([
            'data' => [
                'id' => $image->getKey(),
                'image_url' => $signer->for($image),
                'cached_until' => now()
                    ->addDays(WishlistMediaCollection::CachedImage->retentionDays())
                    ->toIso8601String(),
            ],
        ], JsonResponse::HTTP_CREATED);
    }
}

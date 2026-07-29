<?php

namespace Functional\Catalog\Http\Controllers;

use Functional\Catalog\Models\Product;
use Functional\Users\Enums\Ability;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class VerifyProductController
{
    /**
     * Mark a contributed catalogue entry as reviewed, which is what lets others trust it.
     */
    public function __invoke(Product $product): JsonResponse
    {
        Gate::authorize(Ability::ModerateCatalog->value);

        $product->forceFill(['verified_at' => now()])->save();

        return new JsonResponse([
            'data' => [
                'id' => $product->getKey(),
                'is_verified' => $product->isVerified(),
                'verified_at' => $product->verified_at?->toIso8601String(),
                'source' => $product->source->value,
                'source_label' => $product->source->label(),
            ],
        ]);
    }
}

<?php

namespace Functional\Catalog\Http\Controllers;

use Functional\Catalog\Http\Requests\MergeProductsRequest;
use Functional\Catalog\Models\Product;
use Functional\Catalog\Services\ProductMerger;
use Functional\Users\Enums\Ability;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class MergeProductsController
{
    /**
     * Fold a duplicate contribution into the catalogue entry worth keeping.
     */
    public function __invoke(
        MergeProductsRequest $request,
        Product $product,
        ProductMerger $merger,
    ): JsonResponse {
        Gate::authorize(Ability::ModerateCatalog->value);

        $survivor = Product::query()->findOrFail($request->string('into')->toString());

        $merged = $merger->merge($product, $survivor);

        return new JsonResponse([
            'data' => [
                'id' => $merged->getKey(),
                'name' => $merged->name,
                'variant_count' => $merged->variants()->count(),
                'is_verified' => $merged->isVerified(),
            ],
        ]);
    }
}

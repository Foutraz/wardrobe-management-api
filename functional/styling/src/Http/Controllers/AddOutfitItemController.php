<?php

namespace Functional\Styling\Http\Controllers;

use Functional\Styling\Enums\OutfitSlot;
use Functional\Styling\Http\Requests\AddOutfitItemRequest;
use Functional\Styling\Models\Outfit;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AddOutfitItemController
{
    /**
     * Put a garment or a wished-for thing into the outfit at a given slot.
     */
    public function __invoke(AddOutfitItemRequest $request, Outfit $outfit): JsonResponse
    {
        Gate::authorize('update', $outfit);

        $slot = $request->enum('slot', OutfitSlot::class);

        $outfitItem = $outfit->items()->create([
            'garment_id' => $request->string('garment_id')->toString() ?: null,
            'wishlist_item_id' => $request->string('wishlist_item_id')->toString() ?: null,
            'slot' => $slot,
            'sort' => $request->integer('sort', $slot->layoutOrder()),
        ]);

        return new JsonResponse([
            'data' => [
                'id' => $outfitItem->getKey(),
                'slot' => $slot->value,
                'label' => $slot->label(),
                'is_owned' => $outfitItem->isOwned(),
            ],
        ], JsonResponse::HTTP_CREATED);
    }
}

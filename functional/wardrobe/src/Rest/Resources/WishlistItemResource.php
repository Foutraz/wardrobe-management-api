<?php

namespace Functional\Wardrobe\Rest\Resources;

use Functional\Wardrobe\Models\WishlistItem;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;

class WishlistItemResource extends Resource
{
    public static $model = WishlistItem::class;

    /**
     * @return array<int, string>
     */
    public function fields(RestRequest $request): array
    {
        return [
            'id',
            'name',
            'brand_label',
            'size_label',
            'colour_name',
            'colour_hex',
            'external_url',
            'price_cents',
            'currency',
            'product_variant_id',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<int, int>
     */
    public function limits(RestRequest $request): array
    {
        return [10, 25, 50];
    }

    public function isAuthorizingEnabled(): bool
    {
        return true;
    }

    /**
     * Restrict every read to the wishlist of the authenticated account.
     */
    public function searchQuery(RestRequest $request, Builder $query)
    {
        return $query->controlled();
    }
}

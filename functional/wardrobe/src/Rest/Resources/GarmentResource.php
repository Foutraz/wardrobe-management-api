<?php

namespace Functional\Wardrobe\Rest\Resources;

use Functional\Wardrobe\Models\Garment;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;

class GarmentResource extends Resource
{
    public static $model = Garment::class;

    /**
     * @return array<int, string>
     */
    public function fields(RestRequest $request): array
    {
        return [
            'id',
            'name',
            'size_label',
            'colour_name',
            'colour_hex',
            'material_composition',
            'condition',
            'availability_status',
            'purchase_price_cents',
            'purchase_currency',
            'purchased_at',
            'notes',
            'category_id',
            'brand_id',
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
        return [10, 25, 50, 100];
    }

    public function isAuthorizingEnabled(): bool
    {
        return true;
    }

    /**
     * Restrict every read to the wardrobe of the authenticated account.
     */
    public function searchQuery(RestRequest $request, Builder $query)
    {
        return $query->controlled();
    }
}

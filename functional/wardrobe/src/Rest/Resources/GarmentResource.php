<?php

namespace Functional\Wardrobe\Rest\Resources;

use Functional\Users\Models\User;
use Functional\Wardrobe\Access\Controls\GarmentControl;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
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
     *
     * @throws AuthenticationException
     */
    public function searchQuery(RestRequest $request, Builder $query)
    {
        $user = $request->user();

        if (! $user instanceof User || ! $query instanceof EloquentBuilder) {
            throw new AuthenticationException;
        }

        return GarmentControl::new()->queried($query, $user);
    }
}

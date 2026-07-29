<?php

namespace Functional\Resale\Rest\Resources;

use Functional\Resale\Access\Controls\VintedListingDraftControl;
use Functional\Resale\Models\VintedListingDraft;
use Functional\Users\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;

class VintedListingDraftResource extends Resource
{
    public static $model = VintedListingDraft::class;

    /**
     * @return array<int, string>
     */
    public function fields(RestRequest $request): array
    {
        return [
            'id',
            'garment_id',
            'title',
            'description',
            'brand_label',
            'size_label',
            'colour_label',
            'condition_label',
            'price_cents',
            'currency',
            'status',
            'handed_off_at',
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
     * Restrict every read to the listing drafts of the authenticated seller.
     *
     * @throws AuthenticationException
     */
    public function searchQuery(RestRequest $request, Builder $query)
    {
        $user = $request->user();

        if (! $user instanceof User || ! $query instanceof EloquentBuilder) {
            throw new AuthenticationException;
        }

        return VintedListingDraftControl::new()->queried($query, $user);
    }
}

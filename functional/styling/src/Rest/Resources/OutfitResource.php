<?php

namespace Functional\Styling\Rest\Resources;

use Functional\Styling\Access\Controls\OutfitControl;
use Functional\Styling\Models\Outfit;
use Functional\Users\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;

class OutfitResource extends Resource
{
    public static $model = Outfit::class;

    /**
     * @return array<int, string>
     */
    public function fields(RestRequest $request): array
    {
        return [
            'id',
            'name',
            'occasion',
            'season',
            'notes',
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
     * Restrict every read to the outfits of the authenticated account.
     *
     * @throws AuthenticationException
     */
    public function searchQuery(RestRequest $request, Builder $query)
    {
        $user = $request->user();

        if (! $user instanceof User || ! $query instanceof EloquentBuilder) {
            throw new AuthenticationException;
        }

        return OutfitControl::new()->queried($query, $user);
    }
}

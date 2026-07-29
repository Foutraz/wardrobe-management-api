<?php

namespace Functional\Styling\Rest\Resources;

use Functional\Styling\Access\Controls\OutfitPlanControl;
use Functional\Styling\Models\OutfitPlan;
use Functional\Users\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;

class OutfitPlanResource extends Resource
{
    public static $model = OutfitPlan::class;

    /**
     * @return array<int, string>
     */
    public function fields(RestRequest $request): array
    {
        return ['id', 'outfit_id', 'scheduled_for', 'created_at', 'updated_at'];
    }

    /**
     * @return array<int, int>
     */
    public function limits(RestRequest $request): array
    {
        return [10, 31, 100];
    }

    public function isAuthorizingEnabled(): bool
    {
        return true;
    }

    /**
     * Restrict every read to the calendar of the authenticated account.
     *
     * @throws AuthenticationException
     */
    public function searchQuery(RestRequest $request, Builder $query)
    {
        $user = $request->user();

        if (! $user instanceof User || ! $query instanceof EloquentBuilder) {
            throw new AuthenticationException;
        }

        return OutfitPlanControl::new()->queried($query, $user);
    }
}

<?php

namespace Functional\Styling\Rest\Resources;

use Functional\Styling\Access\Controls\AvatarControl;
use Functional\Styling\Models\Avatar;
use Functional\Users\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;

class AvatarResource extends Resource
{
    public static $model = Avatar::class;

    /**
     * @return array<int, string>
     */
    public function fields(RestRequest $request): array
    {
        return ['id', 'name', 'created_at', 'updated_at'];
    }

    /**
     * @return array<int, int>
     */
    public function limits(RestRequest $request): array
    {
        return [10, 25];
    }

    public function isAuthorizingEnabled(): bool
    {
        return true;
    }

    /**
     * Restrict every read to the avatars of the authenticated account.
     *
     * @throws AuthenticationException
     */
    public function searchQuery(RestRequest $request, Builder $query)
    {
        $user = $request->user();

        if (! $user instanceof User || ! $query instanceof EloquentBuilder) {
            throw new AuthenticationException;
        }

        return AvatarControl::new()->queried($query, $user);
    }
}

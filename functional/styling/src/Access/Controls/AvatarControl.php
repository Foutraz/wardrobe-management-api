<?php

namespace Functional\Styling\Access\Controls;

use Functional\Styling\Models\Avatar;
use Functional\Users\Enums\Ability;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Lomkit\Access\Controls\Control;
use Lomkit\Access\Perimeters\Perimeter;

class AvatarControl extends Control
{
    protected string $model = Avatar::class;

    /**
     * An avatar is visible to the account it belongs to and to nobody else.
     *
     * @return array<Perimeter>
     */
    protected function perimeters(): array
    {
        return [
            Perimeter::new()
                ->allowed(fn (User $user, string $method): bool => $user->can(
                    Ability::ManageAvatar->value,
                ))
                ->should(fn (User $user, Avatar $avatar): bool => $avatar->user_id === $user->getKey())
                ->query(fn (Builder $query, User $user): Builder => $query->where(
                    'user_id',
                    $user->getKey(),
                )),
        ];
    }
}

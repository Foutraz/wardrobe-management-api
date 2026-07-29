<?php

namespace Functional\Identification\Access\Controls;

use Functional\Identification\Models\IdentificationRequest;
use Functional\Users\Enums\Ability;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Lomkit\Access\Controls\Control;
use Lomkit\Access\Perimeters\Perimeter;

class IdentificationRequestControl extends Control
{
    protected string $model = IdentificationRequest::class;

    /**
     * An identification is visible to the account that submitted it and to nobody else.
     *
     * @return array<Perimeter>
     */
    protected function perimeters(): array
    {
        return [
            Perimeter::new()
                ->allowed(fn (User $user, string $method): bool => $user->can(
                    Ability::IdentifyGarment->value,
                ))
                ->should(fn (User $user, IdentificationRequest $request): bool => $request->user_id === $user->getKey())
                ->query(fn (Builder $query, User $user): Builder => $query->where(
                    'user_id',
                    $user->getKey(),
                )),
        ];
    }
}

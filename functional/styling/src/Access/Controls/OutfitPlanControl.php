<?php

namespace Functional\Styling\Access\Controls;

use Functional\Styling\Models\OutfitPlan;
use Functional\Users\Enums\Ability;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Lomkit\Access\Controls\Control;
use Lomkit\Access\Perimeters\Perimeter;

class OutfitPlanControl extends Control
{
    protected string $model = OutfitPlan::class;

    /**
     * A planned outfit is visible to the account that scheduled it and to nobody else.
     *
     * @return array<Perimeter>
     */
    protected function perimeters(): array
    {
        return [
            Perimeter::new()
                ->allowed(fn (User $user, string $method): bool => $user->can(
                    Ability::ViewOutfit->value,
                ))
                ->should(fn (User $user, OutfitPlan $plan): bool => $plan->user_id === $user->getKey())
                ->query(fn (Builder $query, User $user): Builder => $query->where(
                    'user_id',
                    $user->getKey(),
                )),
        ];
    }
}

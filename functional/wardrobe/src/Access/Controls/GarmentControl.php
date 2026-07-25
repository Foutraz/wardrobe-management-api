<?php

namespace Functional\Wardrobe\Access\Controls;

use Functional\Users\Enums\Ability;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;
use Lomkit\Access\Perimeters\Perimeter;

class GarmentControl extends Control
{
    protected string $model = Garment::class;

    /**
     * A wardrobe is visible to the account that owns it and to nobody else.
     *
     * @return array<Perimeter>
     */
    protected function perimeters(): array
    {
        return [
            Perimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $user->can(
                    self::abilityFor($method)->value,
                ))
                ->should(fn (Model $user, Model $garment): bool => $garment->user_id === $user->getKey())
                ->query(fn (Builder $query, Model $user): Builder => $query->where(
                    'user_id',
                    $user->getKey(),
                )),
        ];
    }

    /**
     * Map an access control method onto the ability that guards it.
     */
    private static function abilityFor(string $method): Ability
    {
        return match ($method) {
            'create' => Ability::CreateGarment,
            'update' => Ability::UpdateGarment,
            'delete', 'restore', 'forceDelete' => Ability::DeleteGarment,
            default => Ability::ViewGarment,
        };
    }
}

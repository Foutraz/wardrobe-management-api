<?php

namespace Functional\Styling\Access\Controls;

use Functional\Styling\Models\Outfit;
use Functional\Users\Enums\Ability;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Lomkit\Access\Controls\Control;
use Lomkit\Access\Perimeters\Perimeter;

class OutfitControl extends Control
{
    protected string $model = Outfit::class;

    /**
     * An outfit is visible to the account that composed it and to nobody else.
     *
     * @return array<Perimeter>
     */
    protected function perimeters(): array
    {
        return [
            Perimeter::new()
                ->allowed(fn (User $user, string $method): bool => $user->can(
                    self::abilityFor($method)->value,
                ))
                ->should(fn (User $user, Outfit $outfit): bool => $outfit->user_id === $user->getKey())
                ->query(fn (Builder $query, User $user): Builder => $query->where(
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
            'create' => Ability::CreateOutfit,
            'update' => Ability::UpdateOutfit,
            'delete', 'restore', 'forceDelete' => Ability::DeleteOutfit,
            default => Ability::ViewOutfit,
        };
    }
}

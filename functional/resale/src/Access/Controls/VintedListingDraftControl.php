<?php

namespace Functional\Resale\Access\Controls;

use Functional\Resale\Models\VintedListingDraft;
use Functional\Users\Enums\Ability;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Lomkit\Access\Controls\Control;
use Lomkit\Access\Perimeters\Perimeter;

class VintedListingDraftControl extends Control
{
    protected string $model = VintedListingDraft::class;

    /**
     * A listing draft is visible to the seller who owns the garment and to nobody else.
     *
     * @return array<Perimeter>
     */
    protected function perimeters(): array
    {
        return [
            Perimeter::new()
                ->allowed(fn (User $user, string $method): bool => $user->can(
                    Ability::ManageResale->value,
                ))
                ->should(fn (User $user, VintedListingDraft $draft): bool => $draft->user_id === $user->getKey())
                ->query(fn (Builder $query, User $user): Builder => $query->where(
                    'user_id',
                    $user->getKey(),
                )),
        ];
    }
}

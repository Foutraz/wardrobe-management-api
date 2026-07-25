<?php

namespace Functional\Wardrobe\Access\Controls;

use Functional\Users\Enums\Ability;
use Functional\Wardrobe\Models\WishlistItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;
use Lomkit\Access\Perimeters\Perimeter;

class WishlistItemControl extends Control
{
    protected string $model = WishlistItem::class;

    /**
     * A wishlist is visible to the account that owns it and to nobody else.
     *
     * @return array<Perimeter>
     */
    protected function perimeters(): array
    {
        return [
            Perimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $user->can(
                    Ability::ManageWishlist->value,
                ))
                ->should(fn (Model $user, Model $item): bool => $item->user_id === $user->getKey())
                ->query(fn (Builder $query, Model $user): Builder => $query->where(
                    'user_id',
                    $user->getKey(),
                )),
        ];
    }
}

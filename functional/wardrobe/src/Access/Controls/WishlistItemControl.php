<?php

namespace Functional\Wardrobe\Access\Controls;

use Functional\Users\Enums\Ability;
use Functional\Users\Models\User;
use Functional\Wardrobe\Models\WishlistItem;
use Illuminate\Database\Eloquent\Builder;
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
                ->allowed(fn (User $user, string $method): bool => $user->can(
                    Ability::ManageWishlist->value,
                ))
                ->should(fn (User $user, WishlistItem $wishlistItem): bool => $wishlistItem->user_id === $user->getKey())
                ->query(fn (Builder $query, User $user): Builder => $query->where(
                    'user_id',
                    $user->getKey(),
                )),
        ];
    }
}

<?php

namespace Functional\Users\Enums;

enum Ability: string
{
    case ViewGarment = 'wardrobe.garment.view';
    case CreateGarment = 'wardrobe.garment.create';
    case UpdateGarment = 'wardrobe.garment.update';
    case DeleteGarment = 'wardrobe.garment.delete';
    case ManageWishlist = 'wardrobe.wishlist.manage';
    case ViewProduct = 'catalog.product.view';
    case ContributeProduct = 'catalog.product.contribute';
    case ModerateCatalog = 'catalog.moderate';

    /**
     * Determine whether this ability belongs to the back-office rather than to an end user.
     */
    public function isModeration(): bool
    {
        return $this === self::ModerateCatalog;
    }

    /**
     * Get the abilities granted to an ordinary account.
     *
     * @return list<self>
     */
    public static function forMembers(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $ability): bool => ! $ability->isModeration(),
        ));
    }
}

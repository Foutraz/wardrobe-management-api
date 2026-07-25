<?php

namespace Functional\Wardrobe\Policies;

use Functional\Wardrobe\Access\Controls\WishlistItemControl;
use Lomkit\Access\Policies\ControlledPolicy;

class WishlistItemPolicy extends ControlledPolicy
{
    protected string $control = WishlistItemControl::class;
}

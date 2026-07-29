<?php

namespace Functional\Wardrobe\Policies;

use Functional\Wardrobe\Access\Controls\GarmentControl;
use Lomkit\Access\Policies\ControlledPolicy;

class GarmentPolicy extends ControlledPolicy
{
    protected string $control = GarmentControl::class;
}

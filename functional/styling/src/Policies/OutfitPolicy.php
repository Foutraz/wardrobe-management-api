<?php

namespace Functional\Styling\Policies;

use Functional\Styling\Access\Controls\OutfitControl;
use Lomkit\Access\Policies\ControlledPolicy;

class OutfitPolicy extends ControlledPolicy
{
    protected string $control = OutfitControl::class;
}

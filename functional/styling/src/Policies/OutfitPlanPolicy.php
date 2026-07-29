<?php

namespace Functional\Styling\Policies;

use Functional\Styling\Access\Controls\OutfitPlanControl;
use Lomkit\Access\Policies\ControlledPolicy;

class OutfitPlanPolicy extends ControlledPolicy
{
    protected string $control = OutfitPlanControl::class;
}

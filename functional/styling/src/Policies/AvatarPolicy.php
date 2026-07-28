<?php

namespace Functional\Styling\Policies;

use Functional\Styling\Access\Controls\AvatarControl;
use Lomkit\Access\Policies\ControlledPolicy;

class AvatarPolicy extends ControlledPolicy
{
    protected string $control = AvatarControl::class;
}

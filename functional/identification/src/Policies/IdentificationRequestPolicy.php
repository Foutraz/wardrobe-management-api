<?php

namespace Functional\Identification\Policies;

use Functional\Identification\Access\Controls\IdentificationRequestControl;
use Lomkit\Access\Policies\ControlledPolicy;

class IdentificationRequestPolicy extends ControlledPolicy
{
    protected string $control = IdentificationRequestControl::class;
}

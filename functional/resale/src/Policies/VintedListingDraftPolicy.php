<?php

namespace Functional\Resale\Policies;

use Functional\Resale\Access\Controls\VintedListingDraftControl;
use Lomkit\Access\Policies\ControlledPolicy;

class VintedListingDraftPolicy extends ControlledPolicy
{
    protected string $control = VintedListingDraftControl::class;
}

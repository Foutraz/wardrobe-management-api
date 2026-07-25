<?php

namespace Functional\Styling\Exceptions;

use Functional\Styling\Enums\RenderStatus;
use RuntimeException;

class IllegalRenderTransition extends RuntimeException
{
    /**
     * Report an attempt to move a render between two states its lifecycle does not connect.
     */
    public static function between(RenderStatus $from, RenderStatus $to): self
    {
        return new self(sprintf(
            'A render cannot move from %s to %s.',
            $from->value,
            $to->value,
        ));
    }
}

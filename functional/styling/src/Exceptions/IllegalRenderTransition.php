<?php

namespace Functional\Styling\Exceptions;

use Functional\Styling\Enums\RenderStatus;
use Illuminate\Http\JsonResponse;
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

    /**
     * Answer with a conflict status, since the render is simply not in a state that allows this move.
     */
    public function render(): JsonResponse
    {
        return new JsonResponse(
            ['message' => $this->getMessage()],
            JsonResponse::HTTP_CONFLICT,
        );
    }
}

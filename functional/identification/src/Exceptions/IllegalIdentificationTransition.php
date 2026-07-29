<?php

namespace Functional\Identification\Exceptions;

use Functional\Identification\Enums\IdentificationStatus;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class IllegalIdentificationTransition extends RuntimeException
{
    /**
     * Report an attempt to move a request between two states its lifecycle does not connect.
     */
    public static function between(IdentificationStatus $from, IdentificationStatus $to): self
    {
        return new self(sprintf(
            'An identification request cannot move from %s to %s.',
            $from->value,
            $to->value,
        ));
    }

    /**
     * Answer with a conflict status, since the request is simply not in a state that allows this move.
     */
    public function render(): JsonResponse
    {
        return new JsonResponse(
            ['message' => $this->getMessage()],
            JsonResponse::HTTP_CONFLICT,
        );
    }
}

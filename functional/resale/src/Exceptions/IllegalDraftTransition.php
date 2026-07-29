<?php

namespace Functional\Resale\Exceptions;

use Functional\Resale\Enums\VintedDraftStatus;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class IllegalDraftTransition extends RuntimeException
{
    /**
     * Report an attempt to move a draft between two states its lifecycle does not connect.
     */
    public static function between(VintedDraftStatus $from, VintedDraftStatus $to): self
    {
        return new self(sprintf(
            'A listing draft cannot move from %s to %s.',
            $from->value,
            $to->value,
        ));
    }

    /**
     * Answer with a conflict status, since the draft is simply not in a state that allows this move.
     */
    public function render(): JsonResponse
    {
        return new JsonResponse(
            ['message' => $this->getMessage()],
            JsonResponse::HTTP_CONFLICT,
        );
    }
}

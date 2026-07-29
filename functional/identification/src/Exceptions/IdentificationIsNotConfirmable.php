<?php

namespace Functional\Identification\Exceptions;

use Functional\Identification\Enums\IdentificationStatus;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class IdentificationIsNotConfirmable extends RuntimeException
{
    /**
     * Report an attempt to confirm a request that is not waiting to be reviewed.
     */
    public static function because(IdentificationStatus $status): self
    {
        return new self(sprintf(
            'An identification request that is %s cannot be confirmed.',
            $status->value,
        ));
    }

    /**
     * Answer with a conflict status, since the request is simply not at the stage this needs.
     */
    public function render(): JsonResponse
    {
        return new JsonResponse(
            ['message' => $this->getMessage()],
            JsonResponse::HTTP_CONFLICT,
        );
    }
}

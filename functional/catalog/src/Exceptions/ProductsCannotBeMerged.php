<?php

namespace Functional\Catalog\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class ProductsCannotBeMerged extends RuntimeException
{
    /**
     * Report an attempt to merge a product into itself.
     */
    public static function intoItself(): self
    {
        return new self('A product cannot be merged into itself.');
    }

    /**
     * Report an attempt to keep a contribution while discarding the reviewed entry.
     */
    public static function becauseTheSurvivorIsUnverified(): self
    {
        return new self('A verified product cannot be merged into an unverified one.');
    }

    /**
     * Answer with an unprocessable status, since the request was understood but the pair is wrong.
     */
    public function render(): JsonResponse
    {
        return new JsonResponse(
            ['message' => $this->getMessage()],
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}

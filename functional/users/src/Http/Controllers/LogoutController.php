<?php

namespace Functional\Users\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutController
{
    /**
     * Revoke the token this call arrived with, leaving other devices signed in.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $current = $request->user()?->currentAccessToken();

        if ($current instanceof PersonalAccessToken) {
            $current->delete();
        }

        return new JsonResponse(status: JsonResponse::HTTP_NO_CONTENT);
    }
}

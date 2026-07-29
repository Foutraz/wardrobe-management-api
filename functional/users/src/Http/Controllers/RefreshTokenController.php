<?php

namespace Functional\Users\Http\Controllers;

use Functional\Users\Models\User;
use Functional\Users\Services\TokenIssuer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class RefreshTokenController
{
    /**
     * Trade the current token for a new one, so a working session never lapses mid-use.
     */
    public function __invoke(Request $request, TokenIssuer $issuer): JsonResponse
    {
        $user = $request->user();
        $current = $user?->currentAccessToken();

        if (! $user instanceof User) {
            abort(JsonResponse::HTTP_UNAUTHORIZED);
        }

        $issued = $issuer->issue($user);

        if ($current instanceof PersonalAccessToken) {
            $current->delete();
        }

        return new JsonResponse(['data' => $issued]);
    }
}

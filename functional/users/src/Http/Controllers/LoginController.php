<?php

namespace Functional\Users\Http\Controllers;

use Functional\Users\Http\Requests\LoginRequest;
use Functional\Users\Models\User;
use Functional\Users\Services\TokenIssuer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController
{
    /**
     * Exchange an email and a password for a bearer token.
     *
     * @throws ValidationException
     */
    public function __invoke(LoginRequest $request, TokenIssuer $issuer): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return new JsonResponse([
            'data' => [
                'user' => [
                    'id' => $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                    'locale' => $user->locale->value,
                ],
                ...$issuer->issue($user),
            ],
        ]);
    }
}

<?php

namespace Functional\Users\Http\Controllers;

use Functional\Users\Enums\Locale;
use Functional\Users\Enums\UserRole;
use Functional\Users\Http\Requests\RegisterRequest;
use Functional\Users\Models\User;
use Functional\Users\Services\TokenIssuer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class RegisterController
{
    /**
     * Open an account and hand it the abilities an ordinary member holds.
     */
    public function __invoke(RegisterRequest $request, TokenIssuer $issuer): JsonResponse
    {
        $user = new User;

        $user->forceFill([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => Hash::make($request->string('password')->toString()),
            'locale' => $request->enum('locale', Locale::class) ?? Locale::French,
        ])->save();

        $user->assignRole(UserRole::Member->value);

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
        ], JsonResponse::HTTP_CREATED);
    }
}

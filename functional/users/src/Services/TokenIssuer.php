<?php

namespace Functional\Users\Services;

use Functional\Users\Models\User;
use Functional\Users\Support\TokenIdleWindow;

class TokenIssuer
{
    public function __construct(
        private readonly TokenIdleWindow $idleWindow,
    ) {}

    /**
     * Hand an account a fresh bearer token and say when it will lapse if left alone.
     *
     * @return array<string, mixed>
     */
    public function issue(User $user): array
    {
        $token = $user->createToken((string) config('users.tokens.name'));

        return [
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'idle_timeout_minutes' => $this->idleWindow->timeoutMinutes(),
            'expires_at' => now()->addMinutes($this->idleWindow->timeoutMinutes())->toIso8601String(),
        ];
    }
}

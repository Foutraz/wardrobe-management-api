<?php

namespace Functional\Users\Support;

use Functional\Users\Models\PersonalAccessToken;

class TokenIdleWindow
{
    /**
     * Decide whether a token has been left idle long enough to stop trusting it.
     *
     * Sanctum only knows absolute expiry, so the sliding window lives here. It leans on
     * last_used_at, which Sanctum stamps after each successful call, and falls back to
     * created_at for a token that has never been used.
     */
    public function isStillFresh(PersonalAccessToken $token): bool
    {
        $lastActivity = $token->last_used_at ?? $token->created_at;

        if ($lastActivity === null) {
            return false;
        }

        return $lastActivity->greaterThan(now()->subMinutes($this->timeoutMinutes()));
    }

    /**
     * Get the number of idle minutes a token survives.
     */
    public function timeoutMinutes(): int
    {
        return (int) config('users.tokens.idle_timeout_minutes');
    }
}

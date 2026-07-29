<?php

namespace Functional\Identification\Listeners;

use Functional\Identification\Models\IdentificationRequest;
use Functional\Users\Models\User;

class CascadeUserIdentificationDeletion
{
    /**
     * Discard the identification history of a departing account.
     */
    public function handle(User $user): void
    {
        IdentificationRequest::query()
            ->where('user_id', $user->id)
            ->cursor()
            ->each(fn (IdentificationRequest $request) => $request->delete());
    }
}

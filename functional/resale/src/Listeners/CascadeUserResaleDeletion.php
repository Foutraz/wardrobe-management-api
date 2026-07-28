<?php

namespace Functional\Resale\Listeners;

use Functional\Resale\Models\VintedListingDraft;
use Functional\Users\Models\User;

class CascadeUserResaleDeletion
{
    /**
     * Discard the listing drafts of a departing account.
     */
    public function handle(User $user): void
    {
        VintedListingDraft::query()
            ->where('user_id', $user->id)
            ->cursor()
            ->each(fn (VintedListingDraft $draft) => $draft->delete());
    }
}

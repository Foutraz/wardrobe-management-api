<?php

namespace Functional\Wardrobe\Listeners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AssignAuthenticatedOwner
{
    /**
     * Attach a new wardrobe record to whoever is signed in, so ownership never travels in the request body.
     */
    public function handle(Model $model): void
    {
        if ($model->user_id !== null || ! Auth::hasUser()) {
            return;
        }

        $model->user_id = Auth::id();
    }
}

<?php

namespace Functional\Styling\Listeners;

use Functional\Styling\Models\Avatar;
use Functional\Styling\Models\AvatarVersion;

class CascadeAvatarDeletion
{
    /**
     * Remove every version of a deleted avatar, which invalidates the previews keyed on them.
     */
    public function handle(Avatar $avatar): void
    {
        AvatarVersion::query()
            ->where('avatar_id', $avatar->id)
            ->cursor()
            ->each(fn (AvatarVersion $avatarVersion) => $avatarVersion->delete());
    }
}

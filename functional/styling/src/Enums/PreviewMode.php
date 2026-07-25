<?php

namespace Functional\Styling\Enums;

enum PreviewMode: string
{
    case FlatLay = 'flat_lay';
    case AvatarTryOn = 'avatar_try_on';

    /**
     * Determine whether the mode needs an avatar version to render against.
     */
    public function requiresAvatar(): bool
    {
        return $this === self::AvatarTryOn;
    }

    /**
     * Determine whether the mode is cheap enough to render inside the request cycle.
     */
    public function isImmediate(): bool
    {
        return $this === self::FlatLay;
    }
}

<?php

namespace Functional\Wardrobe\Enums;

enum WishlistMediaCollection: string
{
    case CachedImage = 'cached_image';

    /**
     * Get the mime types this collection accepts.
     *
     * @return list<string>
     */
    public function acceptedMimeTypes(): array
    {
        return ['image/jpeg', 'image/png', 'image/webp'];
    }

    /**
     * Get how many days a cached copy of someone else's product shot is kept.
     */
    public function retentionDays(): int
    {
        return 7;
    }
}

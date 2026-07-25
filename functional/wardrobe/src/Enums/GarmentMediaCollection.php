<?php

namespace Functional\Wardrobe\Enums;

enum GarmentMediaCollection: string
{
    case Photos = 'photos';
    case Cutouts = 'cutouts';

    /**
     * Get the mime types this collection accepts.
     *
     * @return list<string>
     */
    public function acceptedMimeTypes(): array
    {
        return match ($this) {
            self::Photos => ['image/jpeg', 'image/png', 'image/webp'],
            self::Cutouts => ['image/png'],
        };
    }
}

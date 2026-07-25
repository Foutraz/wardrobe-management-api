<?php

namespace Functional\Styling\Enums;

enum StylingMediaCollection: string
{
    case CanonicalImage = 'canonical_image';
    case Render = 'render';

    /**
     * Get the mime types this collection accepts.
     *
     * @return list<string>
     */
    public function acceptedMimeTypes(): array
    {
        return match ($this) {
            self::CanonicalImage => ['image/jpeg', 'image/png'],
            self::Render => ['image/png'],
        };
    }

    /**
     * Determine whether the collection holds at most one file.
     */
    public function isSingleFile(): bool
    {
        return true;
    }
}

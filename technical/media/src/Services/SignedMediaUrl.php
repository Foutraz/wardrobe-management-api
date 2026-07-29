<?php

namespace Technical\Media\Services;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Technical\Media\Exceptions\MediaCannotBeSigned;

class SignedMediaUrl
{
    /**
     * Get a short-lived link to a media item, so stored objects are never publicly readable.
     *
     * @throws MediaCannotBeSigned
     */
    public function for(?Media $media): ?string
    {
        if ($media === null) {
            return null;
        }

        if ($media->getDiskDriverName() !== 's3') {
            throw MediaCannotBeSigned::onDisk($media->disk);
        }

        return $media->getTemporaryUrl(now()->addMinutes($this->ttlMinutes()));
    }

    /**
     * Get how long a signed link stays valid.
     */
    public function ttlMinutes(): int
    {
        return (int) config('media.signed_url_ttl_minutes');
    }
}

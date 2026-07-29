<?php

namespace Technical\Media\Exceptions;

use RuntimeException;

class MediaCannotBeSigned extends RuntimeException
{
    /**
     * Report a disk that cannot mint temporary links, rather than handing out a permanent one.
     */
    public static function onDisk(string $disk): self
    {
        return new self(sprintf(
            'The "%s" disk cannot sign URLs. Media must live on an S3 compatible disk so links expire.',
            $disk,
        ));
    }
}

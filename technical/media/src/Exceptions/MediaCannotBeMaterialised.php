<?php

namespace Technical\Media\Exceptions;

use RuntimeException;

class MediaCannotBeMaterialised extends RuntimeException
{
    /**
     * Report a scratch path the local disk refused to open for writing.
     */
    public static function at(string $path): self
    {
        return new self(sprintf('Could not open "%s" to copy a stored object onto the local disk.', $path));
    }
}

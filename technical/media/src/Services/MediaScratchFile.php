<?php

namespace Technical\Media\Services;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Technical\Media\Exceptions\MediaCannotBeMaterialised;

class MediaScratchFile
{
    /**
     * Copy a stored object onto the local disk so tools that need a real file can read it.
     *
     * On an S3 disk getPath() returns the object key, not something openable, so anything
     * shelling out to an image tool has to materialise the bytes first.
     *
     * @throws MediaCannotBeMaterialised
     */
    public function materialise(Media $media): string
    {
        $stream = $media->stream();

        $path = tempnam(sys_get_temp_dir(), 'media-').'-'.$media->file_name;
        $target = fopen($path, 'wb');

        if ($target === false) {
            throw MediaCannotBeMaterialised::at($path);
        }

        stream_copy_to_stream($stream, $target);

        fclose($target);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $path;
    }

    /**
     * Drop a scratch file once whatever needed it is done.
     */
    public function release(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
}

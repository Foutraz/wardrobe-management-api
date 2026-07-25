<?php

namespace Technical\Media\Services;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Technical\AiGateway\Services\CutoutService;
use Technical\AiGateway\Values\OperationOutcome;

class CutoutPipeline
{
    public function __construct(
        private readonly CutoutService $cutoutService,
    ) {}

    /**
     * Produce a background-free copy of a media item and file it under its own collection.
     */
    public function run(
        HasMedia&Model $subject,
        Media $source,
        string $destinationCollection,
        ?string $userId = null,
    ): OperationOutcome {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'cutout-').'.png';

        $outcome = $this->cutoutService->removeBackground(
            $source->getPath(),
            $temporaryPath,
            $subject,
            $userId,
        );

        if (! $outcome->status->producedOutput()) {
            $this->discard($temporaryPath);

            return $outcome;
        }

        $subject->addMedia($temporaryPath)
            ->usingFileName($source->uuid.'-cutout.png')
            ->toMediaCollection($destinationCollection);

        return $outcome;
    }

    /**
     * Remove a temporary file left behind by a driver that produced nothing usable.
     */
    private function discard(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
}

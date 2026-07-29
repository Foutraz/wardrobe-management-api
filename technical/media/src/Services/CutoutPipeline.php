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
        private readonly MediaScratchFile $scratch,
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
        $sourcePath = $this->scratch->materialise($source);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'cutout-').'.png';

        $outcome = $this->cutoutService->removeBackground(
            $sourcePath,
            $temporaryPath,
            $subject,
            $userId,
        );

        $this->scratch->release($sourcePath);

        if (! $outcome->status->producedOutput()) {
            $this->scratch->release($temporaryPath);

            return $outcome;
        }

        $subject->addMedia($temporaryPath)
            ->usingFileName($source->uuid.'-cutout.png')
            ->toMediaCollection($destinationCollection);

        return $outcome;
    }
}

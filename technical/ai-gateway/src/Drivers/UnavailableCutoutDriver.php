<?php

namespace Technical\AiGateway\Drivers;

use Technical\AiGateway\Contracts\CutoutDriver;
use Technical\AiGateway\Values\OperationOutcome;

class UnavailableCutoutDriver implements CutoutDriver
{
    public function name(): string
    {
        return 'none';
    }

    public function isAvailable(): bool
    {
        return false;
    }

    /**
     * Say plainly that no cutout happened rather than passing the original image off as one.
     */
    public function removeBackground(string $sourcePath, string $destinationPath): OperationOutcome
    {
        return OperationOutcome::unavailable(
            'No cutout driver is configured. Set AI_CUTOUT_DRIVER once a provider is installed.',
        );
    }
}

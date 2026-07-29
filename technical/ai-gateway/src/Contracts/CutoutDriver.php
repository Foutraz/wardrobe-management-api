<?php

namespace Technical\AiGateway\Contracts;

use Technical\AiGateway\Values\OperationOutcome;

interface CutoutDriver
{
    /**
     * Get the provider name recorded in the operations journal.
     */
    public function name(): string;

    /**
     * Determine whether this machine can actually run the driver right now.
     */
    public function isAvailable(): bool;

    /**
     * Write a background-free copy of the source image to the destination path.
     */
    public function removeBackground(string $sourcePath, string $destinationPath): OperationOutcome;
}

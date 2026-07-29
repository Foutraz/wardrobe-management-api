<?php

namespace Technical\AiGateway\Contracts;

use Technical\AiGateway\Values\AttributeReading;

interface CareLabelReader
{
    /**
     * Get the provider name recorded in the operations journal.
     */
    public function name(): string;

    /**
     * Determine whether this machine can actually run the reader right now.
     */
    public function isAvailable(): bool;

    /**
     * Read the fibre composition, the size and any style code printed on a care label.
     */
    public function read(string $imagePath): AttributeReading;
}

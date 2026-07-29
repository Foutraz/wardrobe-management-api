<?php

namespace Technical\AiGateway\Drivers;

use Technical\AiGateway\Contracts\CareLabelReader;
use Technical\AiGateway\Values\AttributeReading;

class UnavailableCareLabelReader implements CareLabelReader
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
     * Say plainly that no label was read rather than returning empty attributes as a result.
     */
    public function read(string $imagePath): AttributeReading
    {
        return AttributeReading::unavailable(
            'No care label reader is configured. Set AI_CARE_LABEL_DRIVER once one is installed.',
        );
    }
}

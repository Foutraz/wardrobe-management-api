<?php

namespace Technical\AiGateway\Enums;

enum AiOperationKind: string
{
    case GarmentCutout = 'garment_cutout';
    case CareLabelOcr = 'care_label_ocr';
    case AttributeExtraction = 'attribute_extraction';
    case TryOnRender = 'try_on_render';

    /**
     * Determine whether this kind of work is cheap enough to run inside the request cycle.
     */
    public function isSynchronous(): bool
    {
        return $this === self::GarmentCutout;
    }
}

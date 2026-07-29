<?php

namespace Functional\Identification\Enums;

enum IdentificationKind: string
{
    case Barcode = 'barcode';
    case CareLabel = 'care_label';
    case Photo = 'photo';

    /**
     * Determine whether this kind needs an uploaded image to work from.
     */
    public function needsImage(): bool
    {
        return $this !== self::Barcode;
    }

    /**
     * Determine whether this kind can resolve straight to a catalogue variant.
     */
    public function canResolveVariant(): bool
    {
        return $this === self::Barcode;
    }

    /**
     * Get the translated label for this kind.
     */
    public function label(): string
    {
        return __('identification::kind.'.$this->value);
    }
}

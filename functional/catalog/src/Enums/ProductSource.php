<?php

namespace Functional\Catalog\Enums;

enum ProductSource: string
{
    case UserContributed = 'user_contributed';
    case BarcodeLookup = 'barcode_lookup';
    case AffiliateFeed = 'affiliate_feed';
    case Manual = 'manual';

    /**
     * Determine whether a product from this source must be reviewed before it is trusted.
     */
    public function requiresModeration(): bool
    {
        return $this === self::UserContributed;
    }

    /**
     * Get the translated label for this source.
     */
    public function label(): string
    {
        return __('catalog::product_source.'.$this->value);
    }
}

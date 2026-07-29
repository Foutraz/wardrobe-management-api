<?php

namespace Functional\Identification\Services;

use Functional\Catalog\Models\ProductVariant;
use Functional\Wardrobe\Enums\GarmentAvailability;
use Functional\Wardrobe\Enums\GarmentCondition;
use Functional\Wardrobe\Models\Garment;

class GarmentBuilder
{
    /**
     * Build the garment an owner confirmed, taking ownership from the request rather than the payload.
     *
     * @param  array<string, mixed>  $confirmed
     */
    public function build(string $userId, array $confirmed, ?ProductVariant $variant): Garment
    {
        $garment = new Garment;

        $garment->forceFill([
            'user_id' => $userId,
            'product_variant_id' => $variant?->getKey(),
            'category_id' => $confirmed['category_id'],
            'brand_id' => $confirmed['brand_id'] ?? null,
            'name' => $confirmed['name'],
            'size_label' => $confirmed['size_label'] ?? null,
            'colour_name' => $confirmed['colour_name'] ?? null,
            'colour_hex' => $confirmed['colour_hex'] ?? null,
            'material_composition' => $confirmed['material_composition'] ?? null,
            'condition' => GarmentCondition::from((string) $confirmed['condition']),
            'availability_status' => GarmentAvailability::Available,
            'purchase_price_cents' => $confirmed['purchase_price_cents'] ?? null,
            'purchase_currency' => $confirmed['purchase_currency'] ?? null,
        ])->save();

        return $garment;
    }
}

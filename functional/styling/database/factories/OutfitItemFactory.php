<?php

namespace Functional\Styling\Database\Factories;

use Functional\Styling\Enums\OutfitSlot;
use Functional\Styling\Models\Outfit;
use Functional\Styling\Models\OutfitItem;
use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<OutfitItem>
 */
class OutfitItemFactory extends Factory
{
    protected $model = OutfitItem::class;

    public function definition(): array
    {
        return [
            'outfit_id' => Outfit::factory(),
            'garment_id' => Garment::factory(),
            'wishlist_item_id' => null,
            'slot' => Arr::random(OutfitSlot::cases()),
            'sort' => 0,
        ];
    }

    /**
     * Point the item at something the owner is only considering buying.
     */
    public function considering(): static
    {
        return $this->state(fn (array $attributes): array => [
            'garment_id' => null,
            'wishlist_item_id' => WishlistItem::factory(),
        ]);
    }
}

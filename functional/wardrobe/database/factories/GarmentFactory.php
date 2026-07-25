<?php

namespace Functional\Wardrobe\Database\Factories;

use Functional\Catalog\Models\Brand;
use Functional\Catalog\Models\Category;
use Functional\Catalog\Models\ProductVariant;
use Functional\Users\Models\User;
use Functional\Wardrobe\Enums\GarmentAvailability;
use Functional\Wardrobe\Enums\GarmentCondition;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends Factory<Garment>
 */
class GarmentFactory extends Factory
{
    protected $model = Garment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_variant_id' => null,
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'name' => Str::title(faker()->words(2)),
            'size_label' => Arr::random(['XS', 'S', 'M', 'L', 'XL']),
            'colour_name' => faker()->colorName(),
            'colour_hex' => faker()->hexColor(),
            'material_composition' => Arr::random([
                '100% cotton',
                '80% cotton, 20% polyester',
                '100% wool',
                '100% linen',
            ]),
            'condition' => Arr::random(GarmentCondition::cases()),
            'availability_status' => GarmentAvailability::Available,
            'purchase_price_cents' => faker()->number(500, 20000),
            'purchase_currency' => 'EUR',
            'purchased_at' => now()->subDays(faker()->number(1, 900))->toDateString(),
            'notes' => null,
        ];
    }

    /**
     * Put the garment in the laundry basket.
     */
    public function dirty(): static
    {
        return $this->state(fn (array $attributes): array => [
            'availability_status' => GarmentAvailability::Dirty,
        ]);
    }

    /**
     * Link the garment to a catalogue variant rather than leaving it hand-entered.
     */
    public function identified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_variant_id' => ProductVariant::factory(),
        ]);
    }

    /**
     * Leave the purchase price unknown so no cost per wear can be computed.
     */
    public function withoutPurchasePrice(): static
    {
        return $this->state(fn (array $attributes): array => [
            'purchase_price_cents' => null,
            'purchase_currency' => null,
        ]);
    }
}

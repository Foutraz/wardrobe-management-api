<?php

namespace Functional\Wardrobe\Database\Factories;

use Functional\Catalog\Models\ProductVariant;
use Functional\Users\Models\User;
use Functional\Wardrobe\Models\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class WishlistItemFactory extends Factory
{
    protected $model = WishlistItem::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_variant_id' => null,
            'name' => Str::title(faker()->words(2)),
            'brand_label' => Str::title(faker()->words(1)),
            'size_label' => Arr::random(['XS', 'S', 'M', 'L', 'XL']),
            'colour_name' => faker()->colorName(),
            'colour_hex' => faker()->hexColor(),
            'external_url' => 'https://'.faker()->domain().'/product',
            'price_cents' => faker()->number(500, 30000),
            'currency' => 'EUR',
        ];
    }

    /**
     * Link the wished-for item to a known catalogue variant.
     */
    public function identified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_variant_id' => ProductVariant::factory(),
        ]);
    }
}

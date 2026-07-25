<?php

namespace Functional\Catalog\Database\Factories;

use Functional\Catalog\Enums\ProductSource;
use Functional\Catalog\Models\Brand;
use Functional\Catalog\Models\Category;
use Functional\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
            'name' => Str::title(faker()->words(3)),
            'style_reference' => Str::upper(Str::substr(faker()->ulid(), -8)),
            'material_composition' => Arr::random([
                '100% cotton',
                '80% cotton, 20% polyester',
                '100% wool',
                '95% viscose, 5% elastane',
                '100% linen',
            ]),
            'retail_price_cents' => faker()->number(500, 30000),
            'currency' => 'EUR',
            'source' => ProductSource::Manual,
            'verified_at' => now(),
        ];
    }

    /**
     * Present the product as an unreviewed contribution from a user.
     */
    public function contributed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'source' => ProductSource::UserContributed,
            'verified_at' => null,
        ]);
    }
}

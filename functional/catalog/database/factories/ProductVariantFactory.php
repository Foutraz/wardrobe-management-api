<?php

namespace Functional\Catalog\Database\Factories;

use Functional\Catalog\Models\Product;
use Functional\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'size_label' => Arr::random(['XS', 'S', 'M', 'L', 'XL', 'XXL']),
            'colour_name' => faker()->colorName(),
            'colour_hex' => faker()->hexColor(),
            'ean' => (string) faker()->number(1000000000000, 9999999999999),
            'sku' => Str::upper(Str::substr(faker()->ulid(), -10)),
        ];
    }

    /**
     * Leave the variant without a resolvable barcode.
     */
    public function withoutBarcode(): static
    {
        return $this->state(fn (array $attributes): array => ['ean' => null]);
    }
}

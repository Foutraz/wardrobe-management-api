<?php

namespace Functional\Identification\Services;

use Functional\Catalog\Models\ProductVariant;
use Technical\AiGateway\Values\AttributeReading;

class BarcodeResolver
{
    private const PROVIDER = 'catalogue-barcode';

    /**
     * Get the provider name recorded against a resolved request.
     */
    public function name(): string
    {
        return self::PROVIDER;
    }

    /**
     * Look a barcode up in the shared catalogue, which is the only source we fully control.
     */
    public function resolve(string $barcode): AttributeReading
    {
        $variant = ProductVariant::query()
            ->with('product.brand')
            ->where('ean', $barcode)
            ->first();

        if ($variant === null) {
            return AttributeReading::failed('No catalogue entry carries this barcode yet.');
        }

        return AttributeReading::succeeded($this->attributesFrom($variant), 100);
    }

    /**
     * Find the variant a resolved barcode points at, so the request can record the link.
     */
    public function variantFor(string $barcode): ?ProductVariant
    {
        return ProductVariant::query()->where('ean', $barcode)->first();
    }

    /**
     * Flatten a catalogue variant into the attribute shape a garment is created from.
     *
     * @return array<string, mixed>
     */
    private function attributesFrom(ProductVariant $variant): array
    {
        return array_filter([
            'name' => $variant->product->name,
            'brand_id' => $variant->product->brand_id,
            'category_id' => $variant->product->category_id,
            'size_label' => $variant->size_label,
            'colour_name' => $variant->colour_name,
            'colour_hex' => $variant->colour_hex,
            'material_composition' => $variant->product->material_composition,
        ], fn (mixed $attribute): bool => $attribute !== null);
    }
}

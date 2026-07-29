<?php

namespace Functional\Identification\Tests\Feature;

use Functional\Catalog\Models\Brand;
use Functional\Catalog\Models\Product;
use Functional\Catalog\Models\ProductVariant;
use Functional\Identification\Services\BarcodeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Technical\AiGateway\Enums\AiOperationStatus;
use Tests\TestCase;

class BarcodeResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_known_barcode_yields_the_catalogue_attributes(): void
    {
        $brand = Brand::factory()->create(['name' => 'Uniqlo']);
        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'name' => 'Chemise oxford',
            'material_composition' => '100% Cotton',
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'ean' => '3612345678901',
            'size_label' => 'L',
            'colour_name' => 'Bleu ciel',
        ]);

        $reading = app(BarcodeResolver::class)->resolve('3612345678901');

        $this->assertSame(AiOperationStatus::Succeeded, $reading->status);
        $this->assertSame(100, $reading->confidence);
        $this->assertSame('Chemise oxford', $reading->attributes['name']);
        $this->assertSame('L', $reading->attributes['size_label']);
        $this->assertSame('Bleu ciel', $reading->attributes['colour_name']);
        $this->assertSame('100% Cotton', $reading->attributes['material_composition']);
        $this->assertSame($brand->id, $reading->attributes['brand_id']);
        $this->assertSame($product->category_id, $reading->attributes['category_id']);
        $this->assertSame($variant->id, app(BarcodeResolver::class)->variantFor('3612345678901')?->id);
    }

    public function test_an_unknown_barcode_fails_with_a_reason_the_owner_can_act_on(): void
    {
        $reading = app(BarcodeResolver::class)->resolve('0000000000000');

        $this->assertSame(AiOperationStatus::Failed, $reading->status);
        $this->assertSame([], $reading->attributes);
        $this->assertNotNull($reading->failureReason);
        $this->assertNull(app(BarcodeResolver::class)->variantFor('0000000000000'));
    }

    public function test_it_omits_attributes_the_catalogue_does_not_hold(): void
    {
        $product = Product::factory()->create(['material_composition' => null]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'ean' => '3699999999999',
            'colour_name' => null,
            'colour_hex' => null,
        ]);

        $reading = app(BarcodeResolver::class)->resolve('3699999999999');

        $this->assertArrayNotHasKey('material_composition', $reading->attributes);
        $this->assertArrayNotHasKey('colour_name', $reading->attributes);
    }
}

<?php

namespace Functional\Catalog\Tests\Feature;

use Functional\Catalog\Exceptions\ProductsCannotBeMerged;
use Functional\Catalog\Models\Product;
use Functional\Catalog\Models\ProductVariant;
use Functional\Catalog\Services\ProductMerger;
use Functional\Identification\Models\IdentificationRequest;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMergerTest extends TestCase
{
    use RefreshDatabase;

    public function test_variants_the_survivor_lacks_move_across(): void
    {
        $survivor = Product::factory()->create();
        $duplicate = Product::factory()->contributed()->create();

        ProductVariant::factory()->create(['product_id' => $survivor->id, 'size_label' => 'M', 'colour_name' => 'Noir']);
        $moving = ProductVariant::factory()->create(['product_id' => $duplicate->id, 'size_label' => 'L', 'colour_name' => 'Noir']);

        app(ProductMerger::class)->merge($duplicate, $survivor);

        $this->assertSame($survivor->id, $moving->fresh()->product_id);
        $this->assertSame(2, $survivor->variants()->count());
        $this->assertModelMissing($duplicate);
    }

    public function test_a_wardrobe_follows_a_retired_variant_to_its_twin(): void
    {
        $survivor = Product::factory()->create();
        $duplicate = Product::factory()->contributed()->create();

        $twin = ProductVariant::factory()->create(['product_id' => $survivor->id, 'size_label' => 'M', 'colour_name' => 'Noir']);
        $retiring = ProductVariant::factory()->create(['product_id' => $duplicate->id, 'size_label' => 'M', 'colour_name' => 'Noir']);

        $garment = Garment::factory()->create(['product_variant_id' => $retiring->id]);

        app(ProductMerger::class)->merge($duplicate, $survivor);

        $this->assertSame($twin->id, $garment->fresh()->product_variant_id);
        $this->assertModelMissing($retiring);
    }

    public function test_a_past_identification_follows_the_merge_too(): void
    {
        $survivor = Product::factory()->create();
        $duplicate = Product::factory()->contributed()->create();

        $twin = ProductVariant::factory()->create(['product_id' => $survivor->id, 'size_label' => 'S', 'colour_name' => 'Écru']);
        $retiring = ProductVariant::factory()->create(['product_id' => $duplicate->id, 'size_label' => 'S', 'colour_name' => 'Écru']);

        $request = IdentificationRequest::factory()->succeeded()->create([
            'resolved_product_variant_id' => $retiring->id,
        ]);

        app(ProductMerger::class)->merge($duplicate, $survivor);

        $this->assertSame($twin->id, $request->fresh()->resolved_product_variant_id);
    }

    public function test_a_barcode_is_handed_to_a_twin_that_had_none(): void
    {
        $survivor = Product::factory()->create();
        $duplicate = Product::factory()->contributed()->create();

        $twin = ProductVariant::factory()->withoutBarcode()->create([
            'product_id' => $survivor->id, 'size_label' => 'M', 'colour_name' => 'Bleu',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $duplicate->id, 'size_label' => 'M', 'colour_name' => 'Bleu', 'ean' => '3655555555555',
        ]);

        app(ProductMerger::class)->merge($duplicate, $survivor);

        $this->assertSame('3655555555555', $twin->fresh()->ean);
    }

    public function test_a_twin_that_already_has_a_barcode_keeps_it(): void
    {
        $survivor = Product::factory()->create();
        $duplicate = Product::factory()->contributed()->create();

        $twin = ProductVariant::factory()->create([
            'product_id' => $survivor->id, 'size_label' => 'M', 'colour_name' => 'Vert', 'ean' => '3600000000001',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $duplicate->id, 'size_label' => 'M', 'colour_name' => 'Vert', 'ean' => '3600000000002',
        ]);

        app(ProductMerger::class)->merge($duplicate, $survivor);

        $this->assertSame('3600000000001', $twin->fresh()->ean);
    }

    public function test_a_product_cannot_be_merged_into_itself(): void
    {
        $product = Product::factory()->create();

        $this->expectException(ProductsCannotBeMerged::class);

        app(ProductMerger::class)->merge($product, $product);
    }

    public function test_a_reviewed_entry_is_never_discarded_for_a_contribution(): void
    {
        $reviewed = Product::factory()->create();
        $contribution = Product::factory()->contributed()->create();

        $this->expectException(ProductsCannotBeMerged::class);

        app(ProductMerger::class)->merge($reviewed, $contribution);
    }
}

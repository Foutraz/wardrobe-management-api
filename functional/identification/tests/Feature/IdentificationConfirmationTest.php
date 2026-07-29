<?php

namespace Functional\Identification\Tests\Feature;

use Functional\Catalog\Enums\ProductSource;
use Functional\Catalog\Models\Category;
use Functional\Catalog\Models\Product;
use Functional\Catalog\Models\ProductVariant;
use Functional\Identification\Enums\IdentificationKind;
use Functional\Identification\Enums\IdentificationStatus;
use Functional\Identification\Models\IdentificationRequest;
use Functional\Wardrobe\Enums\GarmentCondition;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\MemberAccount;
use Tests\TestCase;

class IdentificationConfirmationTest extends TestCase
{
    use MemberAccount, RefreshDatabase;

    public function test_confirming_a_resolved_scan_reuses_the_catalogue_entry(): void
    {
        $user = $this->member();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['name' => 'Jean brut']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'ean' => '3633333333333',
        ]);

        $request = IdentificationRequest::factory()->succeeded()->create([
            'user_id' => $user->id,
            'barcode' => '3633333333333',
            'resolved_product_variant_id' => $variant->id,
        ]);

        Sanctum::actingAs($user);

        $productsBefore = Product::query()->count();

        $this->postJson("/api/v1/identifications/{$request->id}/confirmation", [
            'name' => 'Jean brut',
            'category_id' => $category->id,
            'condition' => GarmentCondition::VeryGood->value,
        ])
            ->assertCreated()
            ->assertJsonPath('data.product_variant_id', $variant->id)
            ->assertJsonPath('data.contributed_to_catalogue', false);

        $garment = Garment::query()->firstOrFail();

        $this->assertSame($user->id, $garment->user_id);
        $this->assertSame($variant->id, $garment->product_variant_id);
        $this->assertSame(IdentificationStatus::Confirmed, $request->fresh()->status);
        $this->assertSame($garment->id, $request->fresh()->created_garment_id);
        $this->assertSame($productsBefore, Product::query()->count());
    }

    public function test_confirming_an_unresolved_scan_contributes_an_unverified_catalogue_entry(): void
    {
        $user = $this->member();
        $category = Category::factory()->create();

        $request = IdentificationRequest::factory()->succeeded()->create([
            'user_id' => $user->id,
            'kind' => IdentificationKind::CareLabel,
            'barcode' => null,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/identifications/{$request->id}/confirmation", [
            'name' => 'Chemise en lin',
            'category_id' => $category->id,
            'condition' => GarmentCondition::Good->value,
        ])
            ->assertCreated()
            ->assertJsonPath('data.contributed_to_catalogue', true);

        $contributed = Product::query()->where('name', 'Chemise en lin')->firstOrFail();

        $this->assertSame(ProductSource::UserContributed, $contributed->source);
        $this->assertNull($contributed->verified_at);
        $this->assertFalse($contributed->isVerified());
    }

    public function test_a_failed_request_cannot_be_confirmed(): void
    {
        $user = $this->member();
        $request = IdentificationRequest::factory()->failed()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/identifications/{$request->id}/confirmation", $this->payload())
            ->assertConflict();

        $this->assertSame(0, Garment::query()->count());
    }

    public function test_the_same_request_cannot_be_confirmed_twice(): void
    {
        $user = $this->member();
        $request = IdentificationRequest::factory()->succeeded()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $payload = $this->payload();

        $this->postJson("/api/v1/identifications/{$request->id}/confirmation", $payload)->assertCreated();
        $this->postJson("/api/v1/identifications/{$request->id}/confirmation", $payload)->assertConflict();

        $this->assertSame(1, Garment::query()->count());
    }

    public function test_an_account_cannot_confirm_an_identification_it_did_not_submit(): void
    {
        $owner = $this->member();
        $stranger = $this->member();
        $request = IdentificationRequest::factory()->succeeded()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($stranger);

        $this->postJson("/api/v1/identifications/{$request->id}/confirmation", $this->payload())
            ->assertForbidden();

        $this->assertSame(0, Garment::query()->count());
    }

    /**
     * Build the smallest payload a confirmation accepts.
     *
     * @return array<string, string>
     */
    private function payload(): array
    {
        return [
            'name' => 'Veste',
            'category_id' => Category::factory()->create()->id,
            'condition' => GarmentCondition::Good->value,
        ];
    }
}

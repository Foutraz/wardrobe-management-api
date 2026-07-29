<?php

namespace Functional\Identification\Tests\Feature;

use Functional\Catalog\Models\Product;
use Functional\Catalog\Models\ProductVariant;
use Functional\Identification\Enums\IdentificationKind;
use Functional\Identification\Enums\IdentificationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\MemberAccount;
use Tests\TestCase;

class IdentificationApiTest extends TestCase
{
    use MemberAccount, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_it_rejects_an_unauthenticated_submission(): void
    {
        $this->postJson('/api/v1/identifications', [
            'kind' => IdentificationKind::Barcode->value,
            'barcode' => '3611111111111',
        ])->assertUnauthorized();
    }

    public function test_scanning_a_known_barcode_returns_attributes_awaiting_confirmation(): void
    {
        $user = $this->member();
        $product = Product::factory()->create(['name' => 'Pull col rond']);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'ean' => '3611111111111',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/identifications', [
            'kind' => IdentificationKind::Barcode->value,
            'barcode' => '3611111111111',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', IdentificationStatus::Succeeded->value)
            ->assertJsonPath('data.awaits_confirmation', true)
            ->assertJsonPath('data.confidence', 100)
            ->assertJsonPath('data.extracted_attributes.name', 'Pull col rond')
            ->assertJsonPath('data.kind_label', 'Code-barres');
    }

    public function test_scanning_an_unknown_barcode_reports_the_failure(): void
    {
        Sanctum::actingAs($this->member());

        $this->postJson('/api/v1/identifications', [
            'kind' => IdentificationKind::Barcode->value,
            'barcode' => '0000000000009',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', IdentificationStatus::Failed->value)
            ->assertJsonPath('data.awaits_confirmation', false);
    }

    public function test_a_barcode_submission_must_not_carry_an_image(): void
    {
        Sanctum::actingAs($this->member());

        $this->postJson('/api/v1/identifications', [
            'kind' => IdentificationKind::Barcode->value,
            'barcode' => '3611111111111',
            'image' => UploadedFile::fake()->image('inutile.jpg'),
        ])->assertUnprocessable();
    }

    public function test_a_label_submission_must_carry_an_image(): void
    {
        Sanctum::actingAs($this->member());

        $this->postJson('/api/v1/identifications', [
            'kind' => IdentificationKind::CareLabel->value,
        ])->assertUnprocessable();
    }

    public function test_a_label_submission_keeps_the_image_it_was_given(): void
    {
        $user = $this->member();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/identifications', [
            'kind' => IdentificationKind::CareLabel->value,
            'image' => UploadedFile::fake()->image('etiquette.jpg'),
        ])
            ->assertCreated()
            ->assertJsonPath('data.kind', IdentificationKind::CareLabel->value);
    }
}

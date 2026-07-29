<?php

namespace Functional\Identification\Tests\Feature;

use Functional\Catalog\Models\Product;
use Functional\Catalog\Models\ProductVariant;
use Functional\Identification\Enums\IdentificationKind;
use Functional\Identification\Enums\IdentificationStatus;
use Functional\Identification\Models\IdentificationRequest;
use Functional\Identification\Services\IdentificationPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Technical\AiGateway\Contracts\CareLabelReader;
use Technical\AiGateway\Enums\AiOperationKind;
use Technical\AiGateway\Enums\AiOperationStatus;
use Technical\AiGateway\Models\AiOperation;
use Technical\AiGateway\Values\AttributeReading;
use Tests\TestCase;

class IdentificationPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_a_known_barcode_leaves_the_request_awaiting_confirmation(): void
    {
        $product = Product::factory()->create(['name' => 'Jean droit']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'ean' => '3611111111111',
            'size_label' => '40',
        ]);

        $request = IdentificationRequest::factory()->create(['barcode' => '3611111111111']);

        $resolved = app(IdentificationPipeline::class)->run($request);

        $this->assertSame(IdentificationStatus::Succeeded, $resolved->status);
        $this->assertTrue($resolved->status->awaitsConfirmation());
        $this->assertSame('Jean droit', $resolved->extracted_attributes['name']);
        $this->assertSame($variant->id, $resolved->resolved_product_variant_id);
        $this->assertSame(100, $resolved->confidence);
    }

    public function test_an_unknown_barcode_fails_with_a_reason(): void
    {
        $request = IdentificationRequest::factory()->create(['barcode' => '0000000000001']);

        $resolved = app(IdentificationPipeline::class)->run($request);

        $this->assertSame(IdentificationStatus::Failed, $resolved->status);
        $this->assertNotNull($resolved->failure_reason);
        $this->assertNull($resolved->resolved_product_variant_id);
    }

    public function test_a_failed_request_can_be_run_again(): void
    {
        $request = IdentificationRequest::factory()->create(['barcode' => '3622222222222']);
        $pipeline = app(IdentificationPipeline::class);

        $this->assertSame(IdentificationStatus::Failed, $pipeline->run($request)->status);

        $product = Product::factory()->create();
        ProductVariant::factory()->create(['product_id' => $product->id, 'ean' => '3622222222222']);

        $request->transitionTo(IdentificationStatus::Pending);

        $this->assertSame(IdentificationStatus::Succeeded, $pipeline->run($request->fresh())->status);
    }

    public function test_a_care_label_without_a_reader_reports_unavailable_rather_than_empty(): void
    {
        $request = IdentificationRequest::factory()->create([
            'kind' => IdentificationKind::CareLabel,
            'barcode' => null,
        ]);

        $request->addMedia(UploadedFile::fake()->image('etiquette.jpg'))
            ->toMediaCollection(IdentificationRequest::SUBJECT_COLLECTION);

        $resolved = app(IdentificationPipeline::class)->run($request->fresh());

        $this->assertSame(IdentificationStatus::Failed, $resolved->status);
        $this->assertStringContainsString('reader', (string) $resolved->failure_reason);

        $this->assertSame(
            AiOperationStatus::Unavailable,
            AiOperation::query()->latest()->firstOrFail()->status,
        );
    }

    public function test_a_working_care_label_reader_fills_the_attributes_in(): void
    {
        $this->app->instance(CareLabelReader::class, new class implements CareLabelReader
        {
            public function name(): string
            {
                return 'reader-double';
            }

            public function isAvailable(): bool
            {
                return true;
            }

            public function read(string $imagePath): AttributeReading
            {
                return AttributeReading::succeeded(
                    ['material_composition' => '95% Viscose, 5% Elastane', 'size_label' => 'S'],
                    67,
                );
            }
        });

        $request = IdentificationRequest::factory()->create([
            'kind' => IdentificationKind::CareLabel,
            'barcode' => null,
        ]);

        $request->addMedia(UploadedFile::fake()->image('etiquette.jpg'))
            ->toMediaCollection(IdentificationRequest::SUBJECT_COLLECTION);

        $resolved = app(IdentificationPipeline::class)->run($request->fresh());

        $this->assertSame(IdentificationStatus::Succeeded, $resolved->status);
        $this->assertSame('S', $resolved->extracted_attributes['size_label']);
        $this->assertSame(67, $resolved->confidence);
        $this->assertSame('reader-double', $resolved->provider);
    }

    public function test_a_care_label_request_without_an_image_fails(): void
    {
        $request = IdentificationRequest::factory()->create([
            'kind' => IdentificationKind::CareLabel,
            'barcode' => null,
        ]);

        $resolved = app(IdentificationPipeline::class)->run($request);

        $this->assertSame(IdentificationStatus::Failed, $resolved->status);
        $this->assertStringContainsString('image', (string) $resolved->failure_reason);
    }

    public function test_every_run_lands_in_the_operations_journal_against_the_request(): void
    {
        $request = IdentificationRequest::factory()->create(['barcode' => '0000000000002']);

        app(IdentificationPipeline::class)->run($request);

        $operation = AiOperation::query()->latest()->firstOrFail();

        $this->assertSame(AiOperationKind::AttributeExtraction, $operation->kind);
        $this->assertSame($request->getMorphClass(), $operation->subject_type);
        $this->assertSame($request->id, $operation->subject_id);
        $this->assertSame($request->user_id, $operation->user_id);
        $this->assertNotNull($operation->latency_ms);
    }
}

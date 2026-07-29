<?php

namespace Functional\Wardrobe\Tests\Feature;

use Functional\Users\Enums\Ability;
use Functional\Users\Models\Permission;
use Functional\Users\Models\User;
use Functional\Wardrobe\Enums\GarmentMediaCollection;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Technical\AiGateway\Contracts\CutoutDriver;
use Technical\AiGateway\Enums\AiOperationKind;
use Technical\AiGateway\Enums\AiOperationStatus;
use Technical\AiGateway\Models\AiOperation;
use Technical\AiGateway\Values\OperationOutcome;
use Tests\TestCase;

class GarmentPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_a_photo_files_it_under_the_photos_collection(): void
    {
        $user = $this->member();
        $garment = Garment::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/garments/{$garment->id}/photos", [
            'photo' => UploadedFile::fake()->image('marinière.jpg', 800, 1200),
        ])->assertCreated();

        $this->assertCount(1, $garment->fresh()->getMedia(GarmentMediaCollection::Photos->value));
    }

    public function test_it_refuses_anything_that_is_not_an_image(): void
    {
        $user = $this->member();
        $garment = Garment::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/garments/{$garment->id}/photos", [
            'photo' => UploadedFile::fake()->create('invoice.pdf', 120, 'application/pdf'),
        ])->assertUnprocessable();
    }

    public function test_an_account_cannot_add_a_photo_to_a_garment_it_does_not_own(): void
    {
        $owner = $this->member();
        $stranger = $this->member();
        $garment = Garment::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($stranger);

        $this->postJson("/api/v1/garments/{$garment->id}/photos", [
            'photo' => UploadedFile::fake()->image('sneaky.jpg'),
        ])->assertForbidden();
    }

    public function test_an_unavailable_cutout_driver_is_reported_rather_than_faked(): void
    {
        $user = $this->member();
        $garment = Garment::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/garments/{$garment->id}/photos", [
            'photo' => UploadedFile::fake()->image('jean.jpg'),
        ])
            ->assertCreated()
            ->assertJsonPath('data.cutout_status', AiOperationStatus::Unavailable->value);

        $this->assertCount(0, $garment->fresh()->getMedia(GarmentMediaCollection::Cutouts->value));
    }

    public function test_a_working_driver_produces_a_cutout_alongside_the_photo(): void
    {
        $this->swapInWorkingCutoutDriver();

        $user = $this->member();
        $garment = Garment::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/garments/{$garment->id}/photos", [
            'photo' => UploadedFile::fake()->image('pull.jpg'),
        ])
            ->assertCreated()
            ->assertJsonPath('data.cutout_status', AiOperationStatus::Succeeded->value);

        $this->assertCount(1, $garment->fresh()->getMedia(GarmentMediaCollection::Cutouts->value));
    }

    public function test_a_driver_returning_the_wrong_format_fails_visibly_instead_of_crashing(): void
    {
        $this->app->instance(CutoutDriver::class, new class implements CutoutDriver
        {
            public function name(): string
            {
                return 'misbehaving-double';
            }

            public function isAvailable(): bool
            {
                return true;
            }

            public function removeBackground(string $sourcePath, string $destinationPath): OperationOutcome
            {
                copy($sourcePath, $destinationPath);

                return OperationOutcome::succeeded();
            }
        });

        $user = $this->member();
        $garment = Garment::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/garments/{$garment->id}/photos", [
            'photo' => UploadedFile::fake()->image('chemise.jpg'),
        ])
            ->assertCreated()
            ->assertJsonPath('data.cutout_status', AiOperationStatus::Failed->value);

        $this->assertCount(0, $garment->fresh()->getMedia(GarmentMediaCollection::Cutouts->value));

        $this->assertSame(
            AiOperationStatus::Failed,
            AiOperation::query()->latest()->firstOrFail()->status,
            'The journal must record what really happened, not what the driver claimed',
        );
    }

    public function test_every_cutout_attempt_lands_in_the_operations_journal(): void
    {
        $user = $this->member();
        $garment = Garment::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/garments/{$garment->id}/photos", [
            'photo' => UploadedFile::fake()->image('robe.jpg'),
        ])->assertCreated();

        $operation = AiOperation::query()->latest()->firstOrFail();

        $this->assertSame(AiOperationKind::GarmentCutout, $operation->kind);
        $this->assertSame(AiOperationStatus::Unavailable, $operation->status);
        $this->assertSame($user->id, $operation->user_id);
        $this->assertSame($garment->getMorphClass(), $operation->subject_type);
        $this->assertSame($garment->id, $operation->subject_id);
        $this->assertNotNull($operation->latency_ms);
    }

    /**
     * Replace the cutout driver with one that simply copies the source, so the pipeline is exercised.
     */
    private function swapInWorkingCutoutDriver(): void
    {
        $this->app->instance(CutoutDriver::class, new class implements CutoutDriver
        {
            public function name(): string
            {
                return 'copying-double';
            }

            public function isAvailable(): bool
            {
                return true;
            }

            public function removeBackground(string $sourcePath, string $destinationPath): OperationOutcome
            {
                $canvas = imagecreatetruecolor(64, 64);
                imagesavealpha($canvas, true);

                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);

                if ($transparent !== false) {
                    imagefill($canvas, 0, 0, $transparent);
                }

                imagepng($canvas, $destinationPath);
                imagedestroy($canvas);

                return OperationOutcome::succeeded();
            }
        });
    }

    /**
     * Build an account holding the abilities an ordinary member is granted.
     */
    private function member(): User
    {
        foreach (Ability::cases() as $ability) {
            Permission::findOrCreate($ability->value);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();

        $user->givePermissionTo(
            array_map(fn (Ability $ability): string => $ability->value, Ability::forMembers()),
        );

        return $user->fresh();
    }
}

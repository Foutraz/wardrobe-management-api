<?php

namespace Functional\Styling\Tests\Feature;

use Functional\Styling\Enums\OutfitSlot;
use Functional\Styling\Enums\RenderStatus;
use Functional\Styling\Models\Outfit;
use Functional\Styling\Models\OutfitItem;
use Functional\Users\Enums\Ability;
use Functional\Users\Models\Permission;
use Functional\Users\Models\User;
use Functional\Wardrobe\Enums\GarmentMediaCollection;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OutfitApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_it_rejects_an_unauthenticated_search(): void
    {
        $this->postJson('/api/v1/outfits/search', ['search' => []])->assertUnauthorized();
    }

    public function test_a_search_only_returns_the_outfits_of_the_signed_in_account(): void
    {
        $owner = $this->member();
        $stranger = $this->member();

        $mine = Outfit::factory()->count(2)->create(['user_id' => $owner->id]);
        Outfit::factory()->count(3)->create(['user_id' => $stranger->id]);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/outfits/search', ['search' => []])->assertOk();

        $returnedIds = array_column($response->json('data'), 'id');
        sort($returnedIds);

        $expectedIds = $mine->pluck('id')->all();
        sort($expectedIds);

        $this->assertSame($expectedIds, $returnedIds);
    }

    public function test_creating_an_outfit_attaches_it_to_the_signed_in_account(): void
    {
        $user = $this->member();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/outfits/mutate', [
            'mutate' => [[
                'operation' => 'create',
                'attributes' => ['name' => 'Dimanche au marché'],
            ]],
        ])->assertSuccessful();

        $this->assertSame($user->id, Outfit::query()->firstOrFail()->user_id);
    }

    public function test_a_garment_can_be_put_into_an_outfit(): void
    {
        $user = $this->member();
        $outfit = Outfit::factory()->create(['user_id' => $user->id]);
        $garment = Garment::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/outfits/{$outfit->id}/items", [
            'garment_id' => $garment->id,
            'slot' => OutfitSlot::Top->value,
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_owned', true)
            ->assertJsonPath('data.label', 'Haut');
    }

    public function test_an_item_naming_both_a_garment_and_a_wish_is_refused_by_validation(): void
    {
        $user = $this->member();
        $outfit = Outfit::factory()->create(['user_id' => $user->id]);
        $garment = Garment::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/outfits/{$outfit->id}/items", [
            'garment_id' => $garment->id,
            'wishlist_item_id' => $garment->id,
            'slot' => OutfitSlot::Top->value,
        ])->assertUnprocessable();
    }

    public function test_an_account_cannot_touch_an_outfit_it_does_not_own(): void
    {
        $owner = $this->member();
        $stranger = $this->member();
        $outfit = Outfit::factory()->create(['user_id' => $owner->id]);
        $garment = Garment::factory()->create(['user_id' => $stranger->id]);

        Sanctum::actingAs($stranger);

        $this->postJson("/api/v1/outfits/{$outfit->id}/items", [
            'garment_id' => $garment->id,
            'slot' => OutfitSlot::Top->value,
        ])->assertForbidden();

        $this->postJson("/api/v1/outfits/{$outfit->id}/previews")->assertForbidden();
    }

    public function test_asking_for_a_preview_returns_the_rendered_flat_lay(): void
    {
        $user = $this->member();
        $outfit = Outfit::factory()->create(['user_id' => $user->id]);
        $garment = Garment::factory()->create(['user_id' => $user->id]);

        $garment->addMedia(UploadedFile::fake()->image('pull.jpg', 400, 600))
            ->toMediaCollection(GarmentMediaCollection::Photos->value);

        OutfitItem::factory()->create([
            'outfit_id' => $outfit->id,
            'garment_id' => $garment->id,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/outfits/{$outfit->id}/previews")
            ->assertOk()
            ->assertJsonPath('data.status', RenderStatus::Succeeded->value)
            ->assertJsonPath('data.mode', 'flat_lay');
    }

    public function test_a_preview_with_nothing_to_lay_out_answers_accepted_with_a_reason(): void
    {
        $user = $this->member();
        $outfit = Outfit::factory()->create(['user_id' => $user->id]);
        OutfitItem::factory()->create(['outfit_id' => $outfit->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/outfits/{$outfit->id}/previews")
            ->assertAccepted()
            ->assertJsonPath('data.status', RenderStatus::Failed->value);

        $this->assertNotNull($response->json('data.failure_reason'));
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

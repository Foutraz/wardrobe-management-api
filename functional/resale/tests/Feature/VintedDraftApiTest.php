<?php

namespace Functional\Resale\Tests\Feature;

use Functional\Catalog\Models\Brand;
use Functional\Resale\Enums\VintedDraftStatus;
use Functional\Resale\Models\VintedListingDraft;
use Functional\Users\Enums\Ability;
use Functional\Users\Models\Permission;
use Functional\Users\Models\User;
use Functional\Wardrobe\Enums\GarmentAvailability;
use Functional\Wardrobe\Enums\GarmentCondition;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VintedDraftApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_draft_is_composed_from_what_the_garment_already_holds(): void
    {
        $user = $this->member();
        $brand = Brand::factory()->create(['name' => 'Sézane']);
        $garment = Garment::factory()->create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'name' => 'Blazer croisé',
            'size_label' => 'M',
            'colour_name' => 'Marine',
            'condition' => GarmentCondition::VeryGood,
            'availability_status' => GarmentAvailability::Available,
            'purchase_price_cents' => 20000,
            'purchase_currency' => 'EUR',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/garments/{$garment->id}/vinted-draft")
            ->assertCreated()
            ->assertJsonPath('data.brand_label', 'Sézane')
            ->assertJsonPath('data.size_label', 'M')
            ->assertJsonPath('data.colour_label', 'Marine')
            ->assertJsonPath('data.status', VintedDraftStatus::Draft->value)
            ->assertJsonPath('data.price_cents', 9000);

        $this->assertStringContainsString('Sézane', $response->json('data.title'));
        $this->assertStringContainsString('Blazer croisé', $response->json('data.title'));
        $this->assertStringContainsString('Très bon état', $response->json('data.description'));
    }

    public function test_asking_twice_returns_the_draft_already_in_progress(): void
    {
        $user = $this->member();
        $garment = Garment::factory()->create([
            'user_id' => $user->id,
            'availability_status' => GarmentAvailability::Available,
        ]);

        Sanctum::actingAs($user);

        $first = $this->postJson("/api/v1/garments/{$garment->id}/vinted-draft")->assertCreated();
        $second = $this->postJson("/api/v1/garments/{$garment->id}/vinted-draft")->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(1, VintedListingDraft::query()->count());
    }

    public function test_a_garment_in_the_wash_cannot_be_listed(): void
    {
        $user = $this->member();
        $garment = Garment::factory()->dirty()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/garments/{$garment->id}/vinted-draft")->assertStatus(500);

        $this->assertSame(0, VintedListingDraft::query()->count());
    }

    public function test_handing_a_draft_off_returns_the_seller_their_instructions(): void
    {
        $user = $this->member();
        $draft = VintedListingDraft::factory()->ready()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/vinted-listing-drafts/{$draft->id}/status", [
            'status' => VintedDraftStatus::HandedOff->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', VintedDraftStatus::HandedOff->value)
            ->assertJsonPath('data.handoff.publisher', 'assisted-handoff');

        $this->assertNotNull($response->json('data.handed_off_at'));
        $this->assertStringContainsString('vinted.fr', $response->json('data.handoff.target'));
        $this->assertStringContainsString('publiez-la vous-même', $response->json('data.handoff.guidance'));
    }

    public function test_an_illegal_transition_is_refused(): void
    {
        $user = $this->member();
        $draft = VintedListingDraft::factory()->handedOff()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->putJson("/api/v1/vinted-listing-drafts/{$draft->id}/status", [
            'status' => VintedDraftStatus::Draft->value,
        ])->assertStatus(500);

        $this->assertSame(VintedDraftStatus::HandedOff, $draft->fresh()->status);
    }

    public function test_a_search_only_returns_the_drafts_of_the_signed_in_seller(): void
    {
        $owner = $this->member();
        $stranger = $this->member();

        VintedListingDraft::factory()->create(['user_id' => $owner->id]);
        VintedListingDraft::factory()->count(2)->create(['user_id' => $stranger->id]);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/vinted-listing-drafts/search', ['search' => []])->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_a_seller_cannot_move_a_draft_that_is_not_theirs(): void
    {
        $owner = $this->member();
        $stranger = $this->member();
        $draft = VintedListingDraft::factory()->ready()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($stranger);

        $this->putJson("/api/v1/vinted-listing-drafts/{$draft->id}/status", [
            'status' => VintedDraftStatus::HandedOff->value,
        ])->assertForbidden();
    }

    public function test_erasing_a_garment_discards_its_drafts(): void
    {
        $user = $this->member();
        $garment = Garment::factory()->create([
            'user_id' => $user->id,
            'availability_status' => GarmentAvailability::Available,
        ]);
        $draft = VintedListingDraft::factory()->create([
            'user_id' => $user->id,
            'garment_id' => $garment->id,
        ]);

        $garment->forceDelete();

        $this->assertModelMissing($draft);
    }

    public function test_the_api_stores_no_vinted_credentials_anywhere(): void
    {
        $columns = array_map(
            'strval',
            array_keys(VintedListingDraft::factory()->create()->getAttributes()),
        );

        foreach (['token', 'password', 'cookie', 'session', 'access_key'] as $forbidden) {
            foreach ($columns as $column) {
                $this->assertStringNotContainsString($forbidden, $column);
            }
        }
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

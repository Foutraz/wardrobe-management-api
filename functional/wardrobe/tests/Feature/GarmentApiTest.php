<?php

namespace Functional\Wardrobe\Tests\Feature;

use Functional\Catalog\Models\Category;
use Functional\Users\Enums\Ability;
use Functional\Users\Enums\Locale;
use Functional\Users\Models\Permission;
use Functional\Users\Models\User;
use Functional\Wardrobe\Enums\GarmentAvailability;
use Functional\Wardrobe\Enums\GarmentCondition;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GarmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_an_unauthenticated_search(): void
    {
        $this->postJson('/api/v1/garments/search', ['search' => []])
            ->assertUnauthorized();
    }

    public function test_a_search_only_returns_the_wardrobe_of_the_signed_in_account(): void
    {
        $owner = $this->member();
        $stranger = $this->member();

        $mine = Garment::factory()->count(3)->create(['user_id' => $owner->id]);
        Garment::factory()->count(4)->create(['user_id' => $stranger->id]);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/garments/search', ['search' => []])
            ->assertOk();

        $returnedIds = array_column($response->json('data'), 'id');
        sort($returnedIds);

        $expectedIds = $mine->pluck('id')->all();
        sort($expectedIds);

        $this->assertSame($expectedIds, $returnedIds);
    }

    public function test_creating_a_garment_attaches_it_to_the_signed_in_account(): void
    {
        $user = $this->member();
        $category = Category::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/garments/mutate', [
            'mutate' => [[
                'operation' => 'create',
                'attributes' => [
                    'name' => 'Marinière rayée',
                    'category_id' => $category->id,
                    'condition' => GarmentCondition::VeryGood->value,
                    'availability_status' => GarmentAvailability::Available->value,
                    'purchase_price_cents' => 4500,
                    'purchase_currency' => 'EUR',
                ],
            ]],
        ])->assertSuccessful();

        $garment = Garment::query()->where('name', 'Marinière rayée')->firstOrFail();

        $this->assertSame($user->id, $garment->user_id);
    }

    public function test_marking_a_garment_worn_reports_its_cost_per_wear(): void
    {
        $user = $this->member();
        $garment = Garment::factory()->create([
            'user_id' => $user->id,
            'purchase_price_cents' => 9000,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/garments/{$garment->id}/wears", ['worn_on' => '2026-07-20'])
            ->assertCreated()
            ->assertJsonPath('data.wear_count', 1)
            ->assertJsonPath('data.cost_per_wear_cents', 9000);

        $this->postJson("/api/v1/garments/{$garment->id}/wears")
            ->assertCreated()
            ->assertJsonPath('data.wear_count', 2)
            ->assertJsonPath('data.cost_per_wear_cents', 4500);
    }

    public function test_it_refuses_a_wear_date_in_the_future(): void
    {
        $user = $this->member();
        $garment = Garment::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/garments/{$garment->id}/wears", [
            'worn_on' => now()->addDay()->toDateString(),
        ])->assertUnprocessable();
    }

    public function test_moving_a_garment_to_the_laundry_basket_makes_it_unavailable(): void
    {
        $user = $this->member();
        $garment = Garment::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->putJson("/api/v1/garments/{$garment->id}/availability", [
            'availability_status' => GarmentAvailability::Dirty->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.is_available', false)
            ->assertJsonPath('data.label', 'Au sale');

        $this->assertSame(GarmentAvailability::Dirty, $garment->fresh()->availability_status);
    }

    public function test_labels_follow_the_locale_of_the_account_not_the_application_default(): void
    {
        $englishSpeaker = $this->member();
        $englishSpeaker->update(['locale' => Locale::English]);
        $garment = Garment::factory()->create(['user_id' => $englishSpeaker->id]);

        Sanctum::actingAs($englishSpeaker->fresh());

        $this->putJson("/api/v1/garments/{$garment->id}/availability", [
            'availability_status' => GarmentAvailability::Dirty->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.label', 'Dirty');
    }

    public function test_it_rejects_an_unknown_availability_state(): void
    {
        $user = $this->member();
        $garment = Garment::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->putJson("/api/v1/garments/{$garment->id}/availability", [
            'availability_status' => 'incinerated',
        ])->assertUnprocessable();
    }

    public function test_an_account_cannot_touch_a_garment_it_does_not_own(): void
    {
        $owner = $this->member();
        $stranger = $this->member();
        $garment = Garment::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($stranger);

        $this->postJson("/api/v1/garments/{$garment->id}/wears")->assertForbidden();

        $this->putJson("/api/v1/garments/{$garment->id}/availability", [
            'availability_status' => GarmentAvailability::Lent->value,
        ])->assertForbidden();
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

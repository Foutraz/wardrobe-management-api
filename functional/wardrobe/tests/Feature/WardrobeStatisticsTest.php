<?php

namespace Functional\Wardrobe\Tests\Feature;

use Functional\Catalog\Database\Seeders\CatalogSeeder;
use Functional\Catalog\Models\Category;
use Functional\Wardrobe\Enums\GarmentAvailability;
use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WearEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\MemberAccount;
use Tests\TestCase;

class WardrobeStatisticsTest extends TestCase
{
    use MemberAccount, RefreshDatabase;

    public function test_it_is_closed_to_anonymous_callers(): void
    {
        $this->getJson('/api/v1/wardrobe/statistics')->assertUnauthorized();
    }

    public function test_it_reports_the_wardrobe_cost_per_wear(): void
    {
        $user = $this->member();

        $first = Garment::factory()->create(['user_id' => $user->id, 'purchase_price_cents' => 10000]);
        $second = Garment::factory()->create(['user_id' => $user->id, 'purchase_price_cents' => 20000]);

        WearEvent::factory()->count(3)->create(['garment_id' => $first->id]);
        WearEvent::factory()->count(7)->create(['garment_id' => $second->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/wardrobe/statistics')
            ->assertOk()
            ->assertJsonPath('data.garment_count', 2)
            ->assertJsonPath('data.total_spend_cents', 30000)
            ->assertJsonPath('data.wear_count', 10)
            ->assertJsonPath('data.wardrobe_cost_per_wear_cents', 3000);
    }

    public function test_the_cost_per_wear_is_unknown_before_anything_is_worn(): void
    {
        $user = $this->member();
        Garment::factory()->create(['user_id' => $user->id, 'purchase_price_cents' => 5000]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/wardrobe/statistics')
            ->assertOk()
            ->assertJsonPath('data.wear_count', 0)
            ->assertJsonPath('data.wardrobe_cost_per_wear_cents', null);
    }

    public function test_it_counts_what_has_never_been_worn(): void
    {
        $user = $this->member();

        $worn = Garment::factory()->create(['user_id' => $user->id]);
        WearEvent::factory()->create(['garment_id' => $worn->id]);
        Garment::factory()->count(3)->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/wardrobe/statistics')
            ->assertOk()
            ->assertJsonPath('data.never_worn_count', 3);
    }

    public function test_it_counts_only_what_is_available(): void
    {
        $user = $this->member();

        Garment::factory()->count(2)->create([
            'user_id' => $user->id,
            'availability_status' => GarmentAvailability::Available,
        ]);
        Garment::factory()->create([
            'user_id' => $user->id,
            'availability_status' => GarmentAvailability::Dirty,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/wardrobe/statistics')
            ->assertOk()
            ->assertJsonPath('data.garment_count', 3)
            ->assertJsonPath('data.available_count', 2);
    }

    public function test_it_ranks_the_most_and_least_worn(): void
    {
        $user = $this->member();

        $favourite = Garment::factory()->create(['user_id' => $user->id, 'name' => 'Pull favori']);
        $forgotten = Garment::factory()->create(['user_id' => $user->id, 'name' => 'Veste oubliée']);

        WearEvent::factory()->count(9)->create(['garment_id' => $favourite->id]);
        WearEvent::factory()->create(['garment_id' => $forgotten->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/wardrobe/statistics')->assertOk();

        $this->assertSame('Pull favori', $response->json('data.most_worn.0.name'));
        $this->assertSame(9, $response->json('data.most_worn.0.wear_count'));
        $this->assertSame('Veste oubliée', $response->json('data.least_worn.0.name'));
    }

    public function test_it_breaks_the_wardrobe_down_by_translated_category(): void
    {
        (new CatalogSeeder)->run();

        $user = $this->member();
        $tops = Category::query()->where('slug', 'tops')->firstOrFail();

        Garment::factory()->count(2)->create(['user_id' => $user->id, 'category_id' => $tops->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/wardrobe/statistics')->assertOk();

        $this->assertSame('tops', $response->json('data.by_category.0.slug'));
        $this->assertSame('Hauts', $response->json('data.by_category.0.label'));
        $this->assertSame(2, $response->json('data.by_category.0.garment_count'));
    }

    public function test_it_never_leaks_another_wardrobe_into_the_figures(): void
    {
        $owner = $this->member();
        $stranger = $this->member();

        Garment::factory()->create(['user_id' => $owner->id, 'purchase_price_cents' => 1000]);
        Garment::factory()->count(5)->create(['user_id' => $stranger->id, 'purchase_price_cents' => 9999]);

        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/wardrobe/statistics')
            ->assertOk()
            ->assertJsonPath('data.garment_count', 1)
            ->assertJsonPath('data.total_spend_cents', 1000);
    }
}

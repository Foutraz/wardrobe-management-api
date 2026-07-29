<?php

namespace Tests\Feature;

use Functional\Resale\Models\VintedListingDraft;
use Functional\Styling\Models\Avatar;
use Functional\Styling\Models\Outfit;
use Functional\Styling\Models\OutfitPlan;
use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WishlistItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MemberAccount;
use Tests\TestCase;

class RestResourceBoundariesTest extends TestCase
{
    use MemberAccount, RefreshDatabase;

    /**
     * Every owner-scoped REST resource, with the model that backs it.
     *
     * @return array<string, array{string, class-string<Model>}>
     */
    public static function ownedResources(): array
    {
        return [
            'garments' => ['garments', Garment::class],
            'wishlist items' => ['wishlist-items', WishlistItem::class],
            'outfits' => ['outfits', Outfit::class],
            'avatars' => ['avatars', Avatar::class],
            'outfit plans' => ['outfit-plans', OutfitPlan::class],
            'vinted drafts' => ['vinted-listing-drafts', VintedListingDraft::class],
        ];
    }

    #[DataProvider('ownedResources')]
    public function test_reading_the_resource_details_needs_a_token(string $uri, string $model): void
    {
        $this->getJson("/api/v1/{$uri}")->assertUnauthorized();
    }

    #[DataProvider('ownedResources')]
    public function test_the_details_endpoint_describes_the_resource(string $uri, string $model): void
    {
        Sanctum::actingAs($this->member());

        $this->getJson("/api/v1/{$uri}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['fields']]);
    }

    #[DataProvider('ownedResources')]
    public function test_deleting_needs_a_token(string $uri, string $model): void
    {
        $record = $model::factory()->create();

        $this->deleteJson("/api/v1/{$uri}", ['resources' => [$record->getKey()]])
            ->assertUnauthorized();

        $this->assertModelExists($record);
    }

    #[DataProvider('ownedResources')]
    public function test_an_owner_can_delete_their_own_record(string $uri, string $model): void
    {
        $user = $this->member();
        $record = $model::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/{$uri}", ['resources' => [$record->getKey()]])
            ->assertSuccessful();

        $this->assertNull($model::query()->find($record->getKey()));
    }

    #[DataProvider('ownedResources')]
    public function test_a_stranger_cannot_delete_someone_elses_record(string $uri, string $model): void
    {
        $owner = $this->member();
        $stranger = $this->member();
        $record = $model::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($stranger);

        $this->deleteJson("/api/v1/{$uri}", ['resources' => [$record->getKey()]])
            ->assertForbidden();

        $this->assertNotNull(
            $model::query()->find($record->getKey()),
            'a stranger must not be able to erase someone else\'s record',
        );
    }
}

<?php

namespace Functional\Wardrobe\Tests\Feature;

use Functional\Wardrobe\Enums\WishlistMediaCollection;
use Functional\Wardrobe\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\Support\MemberAccount;
use Tests\TestCase;

class WishlistApiTest extends TestCase
{
    use MemberAccount, RefreshDatabase;

    public function test_it_rejects_an_unauthenticated_search(): void
    {
        $this->postJson('/api/v1/wishlist-items/search', ['search' => []])->assertUnauthorized();
    }

    public function test_a_search_only_returns_the_wishlist_of_the_signed_in_account(): void
    {
        $owner = $this->member();
        $stranger = $this->member();

        $mine = WishlistItem::factory()->count(2)->create(['user_id' => $owner->id]);
        WishlistItem::factory()->count(3)->create(['user_id' => $stranger->id]);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/wishlist-items/search', ['search' => []])->assertOk();

        $returnedIds = array_column($response->json('data'), 'id');
        sort($returnedIds);

        $expectedIds = $mine->pluck('id')->all();
        sort($expectedIds);

        $this->assertSame($expectedIds, $returnedIds);
    }

    public function test_adding_a_wished_for_item_attaches_it_to_the_signed_in_account(): void
    {
        $user = $this->member();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/wishlist-items/mutate', [
            'mutate' => [[
                'operation' => 'create',
                'attributes' => [
                    'name' => 'Trench beige',
                    'external_url' => 'https://example.test/trench',
                    'price_cents' => 12900,
                    'currency' => 'EUR',
                ],
            ]],
        ])->assertSuccessful();

        $this->assertSame($user->id, WishlistItem::query()->firstOrFail()->user_id);
    }

    public function test_caching_an_image_says_when_it_will_be_dropped(): void
    {
        $user = $this->member();
        $wishlistItem = WishlistItem::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/wishlist-items/{$wishlistItem->id}/image", [
            'image' => UploadedFile::fake()->image('trench.jpg', 500, 700),
        ])->assertCreated();

        $this->assertCount(1, $wishlistItem->fresh()->getMedia(WishlistMediaCollection::CachedImage->value));
        $this->assertNotNull($response->json('data.cached_until'));
        $this->assertStringContainsString('X-Amz-Signature', (string) $response->json('data.image_url'));
    }

    public function test_an_account_cannot_cache_an_image_on_a_wish_that_is_not_theirs(): void
    {
        $owner = $this->member();
        $stranger = $this->member();
        $wishlistItem = WishlistItem::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($stranger);

        $this->postJson("/api/v1/wishlist-items/{$wishlistItem->id}/image", [
            'image' => UploadedFile::fake()->image('sneaky.jpg'),
        ])->assertForbidden();
    }

    public function test_a_cached_image_survives_inside_its_retention(): void
    {
        $wishlistItem = $this->itemWithCachedImage();

        $this->travel(6)->days();
        $this->assertSame(0, Artisan::call('wardrobe:prune-wishlist-images'));

        $this->assertCount(1, $wishlistItem->fresh()->getMedia(WishlistMediaCollection::CachedImage->value));
    }

    public function test_a_cached_image_is_dropped_once_its_retention_has_elapsed(): void
    {
        $wishlistItem = $this->itemWithCachedImage();

        $this->travel(8)->days();
        $this->assertSame(0, Artisan::call('wardrobe:prune-wishlist-images'));

        $this->assertCount(0, $wishlistItem->fresh()->getMedia(WishlistMediaCollection::CachedImage->value));
    }

    /**
     * Build a wished-for item that already carries a cached product shot.
     */
    private function itemWithCachedImage(): WishlistItem
    {
        $wishlistItem = WishlistItem::factory()->create();

        $wishlistItem->addMedia(UploadedFile::fake()->image('trench.jpg', 400, 600))
            ->toMediaCollection(WishlistMediaCollection::CachedImage->value);

        return $wishlistItem->fresh();
    }
}

<?php

namespace Functional\Wardrobe\Tests\Feature;

use Functional\Users\Models\User;
use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WearEvent;
use Functional\Wardrobe\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CascadeUserDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_an_account_soft_deletes_its_garments(): void
    {
        $user = User::factory()->create();
        $garment = Garment::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertSoftDeleted($garment);
    }

    public function test_soft_deleting_an_account_discards_its_wishlist(): void
    {
        $user = User::factory()->create();
        $wishlistItem = WishlistItem::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertModelMissing($wishlistItem);
    }

    public function test_force_deleting_an_account_erases_its_garments_and_wear_history(): void
    {
        $user = User::factory()->create();
        $garment = Garment::factory()->create(['user_id' => $user->id]);
        $wearEvent = WearEvent::factory()->create(['garment_id' => $garment->id]);

        $user->forceDelete();

        $this->assertModelMissing($garment);
        $this->assertModelMissing($wearEvent);
    }

    public function test_force_deleting_an_account_reaches_garments_already_in_the_bin(): void
    {
        $user = User::factory()->create();
        $garment = Garment::factory()->create(['user_id' => $user->id]);
        $garment->delete();

        $user->forceDelete();

        $this->assertModelMissing($garment);
    }

    public function test_it_leaves_another_account_untouched(): void
    {
        $user = User::factory()->create();
        $bystander = User::factory()->create();
        $theirGarment = Garment::factory()->create(['user_id' => $bystander->id]);

        $user->forceDelete();

        $this->assertModelExists($theirGarment);
    }
}

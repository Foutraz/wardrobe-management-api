<?php

namespace Functional\Styling\Tests\Feature;

use Functional\Styling\Models\Outfit;
use Functional\Styling\Models\OutfitItem;
use Functional\Users\Models\User;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CascadeGarmentOutOfOutfitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_deleting_a_garment_pulls_it_out_of_every_outfit(): void
    {
        $garment = Garment::factory()->create();
        $outfitItem = OutfitItem::factory()->create(['garment_id' => $garment->id]);

        $garment->forceDelete();

        $this->assertModelMissing($garment);
        $this->assertModelMissing($outfitItem);
    }

    public function test_soft_deleting_a_garment_leaves_the_outfit_composition_alone(): void
    {
        $garment = Garment::factory()->create();
        $outfitItem = OutfitItem::factory()->create(['garment_id' => $garment->id]);

        $garment->delete();

        $this->assertSoftDeleted($garment);
        $this->assertModelExists($outfitItem);
    }

    public function test_erasing_a_wished_for_thing_pulls_it_out_of_every_outfit(): void
    {
        $outfitItem = OutfitItem::factory()->considering()->create();
        $wishlistItem = $outfitItem->wishlistItem;

        $wishlistItem->delete();

        $this->assertModelMissing($wishlistItem);
        $this->assertModelMissing($outfitItem);
    }

    public function test_force_deleting_an_account_whose_garments_are_worn_in_outfits_succeeds(): void
    {
        $user = User::factory()->create();
        $outfit = Outfit::factory()->create(['user_id' => $user->id]);
        $garment = Garment::factory()->create(['user_id' => $user->id]);

        OutfitItem::factory()->create([
            'outfit_id' => $outfit->id,
            'garment_id' => $garment->id,
        ]);

        $user->forceDelete();

        $this->assertModelMissing($user);
        $this->assertModelMissing($garment);
        $this->assertModelMissing($outfit);
    }
}

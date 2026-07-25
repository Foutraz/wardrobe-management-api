<?php

namespace Functional\Styling\Tests\Feature;

use Functional\Styling\Enums\OutfitSlot;
use Functional\Styling\Exceptions\OutfitItemMustReferenceExactlyOneThing;
use Functional\Styling\Models\Outfit;
use Functional\Styling\Models\OutfitItem;
use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutfitItemInvariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_item_may_reference_a_garment(): void
    {
        $outfitItem = OutfitItem::factory()->create();

        $this->assertTrue($outfitItem->isOwned());
        $this->assertModelExists($outfitItem);
    }

    public function test_an_item_may_reference_a_wished_for_thing(): void
    {
        $outfitItem = OutfitItem::factory()->considering()->create();

        $this->assertFalse($outfitItem->isOwned());
        $this->assertModelExists($outfitItem);
    }

    public function test_an_item_referencing_nothing_is_refused(): void
    {
        $this->expectException(OutfitItemMustReferenceExactlyOneThing::class);

        OutfitItem::factory()->create([
            'garment_id' => null,
            'wishlist_item_id' => null,
        ]);
    }

    public function test_an_item_referencing_both_is_refused(): void
    {
        $this->expectException(OutfitItemMustReferenceExactlyOneThing::class);

        OutfitItem::factory()->create([
            'garment_id' => Garment::factory()->create()->id,
            'wishlist_item_id' => WishlistItem::factory()->create()->id,
        ]);
    }

    public function test_the_same_garment_cannot_be_added_to_an_outfit_twice(): void
    {
        $outfit = Outfit::factory()->create();
        $garment = Garment::factory()->create();

        OutfitItem::factory()->create([
            'outfit_id' => $outfit->id,
            'garment_id' => $garment->id,
            'slot' => OutfitSlot::Top,
        ]);

        $this->expectExceptionMessageMatches('/UNIQUE|unique/');

        OutfitItem::factory()->create([
            'outfit_id' => $outfit->id,
            'garment_id' => $garment->id,
            'slot' => OutfitSlot::Outer,
        ]);
    }
}

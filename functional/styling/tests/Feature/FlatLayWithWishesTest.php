<?php

namespace Functional\Styling\Tests\Feature;

use Functional\Styling\Enums\RenderStatus;
use Functional\Styling\Enums\StylingMediaCollection;
use Functional\Styling\Models\Outfit;
use Functional\Styling\Models\OutfitItem;
use Functional\Styling\Services\FlatLayRenderer;
use Functional\Wardrobe\Enums\GarmentMediaCollection;
use Functional\Wardrobe\Enums\WishlistMediaCollection;
use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FlatLayWithWishesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_wished_for_item_appears_in_the_flat_lay(): void
    {
        $outfit = Outfit::factory()->create();

        $wishedItem = WishlistItem::factory()->create();
        $wishedItem->addMedia(UploadedFile::fake()->image('trench.jpg', 400, 600))
            ->toMediaCollection(WishlistMediaCollection::CachedImage->value);

        OutfitItem::factory()->considering()->create([
            'outfit_id' => $outfit->id,
            'wishlist_item_id' => $wishedItem->id,
        ]);

        $preview = app(FlatLayRenderer::class)->render($outfit->fresh());

        $this->assertSame(RenderStatus::Succeeded, $preview->status);
        $this->assertCount(1, $preview->getMedia(StylingMediaCollection::Render->value));
    }

    public function test_an_outfit_mixing_owned_and_coveted_lays_both_out(): void
    {
        $outfit = Outfit::factory()->create();

        $garment = Garment::factory()->create();
        $garment->addMedia(UploadedFile::fake()->image('pull.jpg', 400, 600))
            ->toMediaCollection(GarmentMediaCollection::Photos->value);

        $wishedItem = WishlistItem::factory()->create();
        $wishedItem->addMedia(UploadedFile::fake()->image('trench.jpg', 400, 600))
            ->toMediaCollection(WishlistMediaCollection::CachedImage->value);

        OutfitItem::factory()->create(['outfit_id' => $outfit->id, 'garment_id' => $garment->id]);
        OutfitItem::factory()->considering()->create([
            'outfit_id' => $outfit->id,
            'wishlist_item_id' => $wishedItem->id,
        ]);

        $preview = app(FlatLayRenderer::class)->render($outfit->fresh());

        $this->assertSame(RenderStatus::Succeeded, $preview->status);
    }

    public function test_a_wished_for_item_without_a_cached_image_is_simply_left_out(): void
    {
        $outfit = Outfit::factory()->create();

        OutfitItem::factory()->considering()->create(['outfit_id' => $outfit->id]);

        $preview = app(FlatLayRenderer::class)->render($outfit->fresh());

        $this->assertSame(RenderStatus::Failed, $preview->status);
        $this->assertNotNull($preview->failure_reason);
    }

    public function test_dropping_the_cached_image_changes_the_render_key(): void
    {
        $outfit = Outfit::factory()->create();

        $wishedItem = WishlistItem::factory()->create();
        $wishedItem->addMedia(UploadedFile::fake()->image('trench.jpg', 400, 600))
            ->toMediaCollection(WishlistMediaCollection::CachedImage->value);

        OutfitItem::factory()->considering()->create([
            'outfit_id' => $outfit->id,
            'wishlist_item_id' => $wishedItem->id,
        ]);

        $renderer = app(FlatLayRenderer::class);
        $first = $renderer->render($outfit->fresh());

        $this->assertSame(RenderStatus::Succeeded, $first->status);

        $wishedItem->clearMediaCollection(WishlistMediaCollection::CachedImage->value);

        $second = $renderer->render($outfit->fresh());

        $this->assertSame($first->id, $second->id, 'the cached render survives the image being pruned');
        $this->assertSame(RenderStatus::Succeeded, $second->status);
    }
}

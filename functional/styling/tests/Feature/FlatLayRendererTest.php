<?php

namespace Functional\Styling\Tests\Feature;

use Functional\Styling\Enums\PreviewMode;
use Functional\Styling\Enums\RenderStatus;
use Functional\Styling\Enums\StylingMediaCollection;
use Functional\Styling\Models\Outfit;
use Functional\Styling\Models\OutfitItem;
use Functional\Styling\Models\OutfitPreview;
use Functional\Styling\Services\FlatLayRenderer;
use Functional\Wardrobe\Enums\GarmentMediaCollection;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FlatLayRendererTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lays_out_the_garments_that_have_an_image(): void
    {
        $outfit = $this->outfitWithPhotographedGarments(3);

        $preview = app(FlatLayRenderer::class)->render($outfit);

        $this->assertSame(RenderStatus::Succeeded, $preview->status);
        $this->assertSame(PreviewMode::FlatLay, $preview->mode);
        $this->assertCount(1, $preview->getMedia(StylingMediaCollection::Render->value));
    }

    public function test_the_rendered_flat_lay_actually_contains_the_garments(): void
    {
        $outfit = $this->outfitWithPhotographedGarments(2);

        $preview = app(FlatLayRenderer::class)->render($outfit);
        $render = $preview->getFirstMedia(StylingMediaCollection::Render->value);

        $this->assertNotNull($render);

        $canvas = imagecreatefromstring((string) stream_get_contents($render->stream()));

        $this->assertNotFalse($canvas, 'the render must be a decodable image');
        $this->assertGreaterThan(0, $this->opaquePixelCount($canvas), 'a blank canvas means nothing was drawn');
    }

    /**
     * Count pixels that are not fully transparent, sampling a grid to keep it quick.
     */
    private function opaquePixelCount(\GdImage $canvas): int
    {
        $opaque = 0;
        $width = imagesx($canvas);
        $height = imagesy($canvas);

        for ($x = 0; $x < $width; $x += 8) {
            for ($y = 0; $y < $height; $y += 8) {
                if (((imagecolorat($canvas, $x, $y) >> 24) & 0x7F) < 127) {
                    $opaque++;
                }
            }
        }

        return $opaque;
    }

    public function test_asking_twice_reuses_the_cached_render(): void
    {
        $outfit = $this->outfitWithPhotographedGarments(2);
        $renderer = app(FlatLayRenderer::class);

        $first = $renderer->render($outfit);
        $second = $renderer->render($outfit);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, OutfitPreview::query()->count());
    }

    public function test_an_outfit_without_a_single_image_fails_visibly(): void
    {
        $outfit = Outfit::factory()->create();
        OutfitItem::factory()->count(2)->create(['outfit_id' => $outfit->id]);

        $preview = app(FlatLayRenderer::class)->render($outfit);

        $this->assertSame(RenderStatus::Failed, $preview->status);
        $this->assertNotNull($preview->failure_reason);
        $this->assertCount(0, $preview->getMedia(StylingMediaCollection::Render->value));
    }

    public function test_a_failed_render_is_attempted_again_on_the_next_ask(): void
    {
        $outfit = Outfit::factory()->create();
        $outfitItem = OutfitItem::factory()->create(['outfit_id' => $outfit->id]);
        $renderer = app(FlatLayRenderer::class);

        $this->assertSame(RenderStatus::Failed, $renderer->render($outfit)->status);

        $outfitItem->garment->addMedia(UploadedFile::fake()->image('pull.jpg', 400, 600))
            ->toMediaCollection(GarmentMediaCollection::Photos->value);

        $retried = $renderer->render($outfit->fresh());

        $this->assertSame(RenderStatus::Succeeded, $retried->status);
        $this->assertSame(1, OutfitPreview::query()->count());
    }

    public function test_changing_the_outfit_produces_a_separate_render(): void
    {
        $outfit = $this->outfitWithPhotographedGarments(2);
        $renderer = app(FlatLayRenderer::class);

        $first = $renderer->render($outfit);

        $this->attachPhotographedGarment($outfit);

        $second = $renderer->render($outfit->fresh());

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, OutfitPreview::query()->count());
    }

    /**
     * Build an outfit whose garments all carry a photo.
     */
    private function outfitWithPhotographedGarments(int $count): Outfit
    {
        $outfit = Outfit::factory()->create();

        for ($position = 0; $position < $count; $position++) {
            $this->attachPhotographedGarment($outfit);
        }

        return $outfit->fresh();
    }

    /**
     * Attach one photographed garment to the outfit.
     */
    private function attachPhotographedGarment(Outfit $outfit): void
    {
        $garment = Garment::factory()->create();

        $garment->addMedia(UploadedFile::fake()->image('piece.jpg', 400, 600))
            ->toMediaCollection(GarmentMediaCollection::Photos->value);

        OutfitItem::factory()->create([
            'outfit_id' => $outfit->id,
            'garment_id' => $garment->id,
        ]);
    }
}

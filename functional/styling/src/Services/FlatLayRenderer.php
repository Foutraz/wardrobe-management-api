<?php

namespace Functional\Styling\Services;

use Functional\Styling\Enums\PreviewMode;
use Functional\Styling\Enums\RenderStatus;
use Functional\Styling\Enums\StylingMediaCollection;
use Functional\Styling\Models\Outfit;
use Functional\Styling\Models\OutfitItem;
use Functional\Styling\Models\OutfitPreview;
use Functional\Styling\Values\PreviewCacheKey;
use Functional\Wardrobe\Enums\GarmentMediaCollection;
use Functional\Wardrobe\Enums\WishlistMediaCollection;
use Illuminate\Database\Eloquent\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Technical\Media\Services\ImageMontage;
use Technical\Media\Services\MediaScratchFile;

class FlatLayRenderer
{
    private const PROVIDER = 'flat-lay';

    public function __construct(
        private readonly ImageMontage $montage,
        private readonly MediaScratchFile $scratch,
    ) {}

    /**
     * Return the outfit's flat lay, rendering it only when no cached one already exists.
     */
    public function render(Outfit $outfit): OutfitPreview
    {
        $outfitItems = $outfit->items()->with(['garment', 'wishlistItem'])->get();

        $cacheKey = PreviewCacheKey::for(
            PreviewMode::FlatLay,
            null,
            array_values($outfitItems->map(fn (OutfitItem $outfitItem): string => $outfitItem->cacheIdentifier())->all()),
        );

        $cached = OutfitPreview::query()->where('cache_key', $cacheKey->value)->first();

        if ($cached !== null && $cached->status->hasImage()) {
            return $cached;
        }

        $preview = $cached ?? OutfitPreview::create([
            'outfit_id' => $outfit->id,
            'mode' => PreviewMode::FlatLay,
            'status' => RenderStatus::Pending,
            'cache_key' => $cacheKey->value,
            'provider' => self::PROVIDER,
        ]);

        if ($preview->status === RenderStatus::Failed) {
            $preview->transitionTo(RenderStatus::Pending);
        }

        $preview->transitionTo(RenderStatus::Processing);

        return $this->draw($preview, $outfitItems);
    }

    /**
     * Compose the montage and settle the preview on the outcome.
     *
     * @param  Collection<int, OutfitItem>  $outfitItems
     */
    private function draw(OutfitPreview $preview, Collection $outfitItems): OutfitPreview
    {
        $sourcePaths = $this->sourcePaths($outfitItems);

        if ($sourcePaths === []) {
            $preview->transitionTo(
                RenderStatus::Failed,
                'None of the items in this outfit has an image to lay out yet.',
            );

            return $preview;
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'flat-lay-').'.png';

        $composed = $this->montage->compose($sourcePaths, $temporaryPath);

        foreach ($sourcePaths as $scratchPath) {
            $this->scratch->release($scratchPath);
        }

        if (! $composed) {
            $this->discard($temporaryPath);
            $preview->transitionTo(RenderStatus::Failed, 'The flat lay could not be composed.');

            return $preview;
        }

        $preview->addMedia($temporaryPath)
            ->usingFileName($preview->id.'-flat-lay.png')
            ->toMediaCollection(StylingMediaCollection::Render->value);

        $preview->transitionTo(RenderStatus::Succeeded);

        return $preview;
    }

    /**
     * Collect one image per item, preferring the background-free copy over the raw photo.
     *
     * @param  Collection<int, OutfitItem>  $outfitItems
     * @return list<string>
     */
    private function sourcePaths(Collection $outfitItems): array
    {
        $paths = [];

        foreach ($outfitItems->sortBy(fn (OutfitItem $outfitItem): int => $outfitItem->slot->layoutOrder()) as $outfitItem) {
            $image = $this->imageFor($outfitItem);

            if ($image !== null) {
                $paths[] = $this->scratch->materialise($image);
            }
        }

        return $paths;
    }

    /**
     * Find the best image for an item, whether it is owned or merely coveted.
     */
    private function imageFor(OutfitItem $outfitItem): ?Media
    {
        $garment = $outfitItem->garment;

        if ($garment !== null) {
            return $garment->getFirstMedia(GarmentMediaCollection::Cutouts->value)
                ?? $garment->getFirstMedia(GarmentMediaCollection::Photos->value);
        }

        return $outfitItem->wishlistItem?->getFirstMedia(WishlistMediaCollection::CachedImage->value);
    }

    /**
     * Remove a temporary file left behind by a montage that produced nothing usable.
     */
    private function discard(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
}

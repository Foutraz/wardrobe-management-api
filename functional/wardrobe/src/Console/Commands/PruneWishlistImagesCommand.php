<?php

namespace Functional\Wardrobe\Console\Commands;

use Functional\Wardrobe\Enums\WishlistMediaCollection;
use Illuminate\Console\Command;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PruneWishlistImagesCommand extends Command
{
    protected $signature = 'wardrobe:prune-wishlist-images';

    protected $description = 'Drop cached product shots of wished-for items once their retention has elapsed';

    /**
     * Let go of images belonging to somebody else's shop as soon as they stop being useful.
     */
    public function handle(): int
    {
        $mediaCollection = WishlistMediaCollection::CachedImage;
        $cutoff = now()->subDays($mediaCollection->retentionDays());

        $pruned = 0;

        Media::query()
            ->where('collection_name', $mediaCollection->value)
            ->where('created_at', '<=', $cutoff)
            ->cursor()
            ->each(function (Media $media) use (&$pruned): void {
                $media->delete();
                $pruned++;
            });

        $this->components->info(sprintf('%d cached wishlist image(s) pruned.', $pruned));

        return self::SUCCESS;
    }
}

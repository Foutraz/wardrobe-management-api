<?php

namespace Functional\Wardrobe\Providers;

use Functional\Catalog\Events\ProductVariantMerged;
use Functional\Users\Models\User;
use Functional\Wardrobe\Access\Controls\GarmentControl;
use Functional\Wardrobe\Access\Controls\WishlistItemControl;
use Functional\Wardrobe\Console\Commands\PruneWishlistImagesCommand;
use Functional\Wardrobe\Database\Seeders\WardrobeSeeder;
use Functional\Wardrobe\Listeners\AssignAuthenticatedOwner;
use Functional\Wardrobe\Listeners\CascadeGarmentDeletion;
use Functional\Wardrobe\Listeners\CascadeUserDeletion;
use Functional\Wardrobe\Listeners\RepointGarmentsAfterVariantMerge;
use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WishlistItem;
use Illuminate\Support\Facades\Event;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class WardrobeServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'wardrobe');

        Garment::creating(AssignAuthenticatedOwner::class);
        WishlistItem::creating(AssignAuthenticatedOwner::class);

        User::deleting(CascadeUserDeletion::class);
        Garment::deleting(CascadeGarmentDeletion::class);

        Event::listen(ProductVariantMerged::class, RepointGarmentsAfterVariantMerge::class);

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadSeeders([WardrobeSeeder::class]);
            $this->commands([PruneWishlistImagesCommand::class]);
        }

        $this->withRouting(
            web: __DIR__.'/../../routes/web.php',
            api: __DIR__.'/../../routes/api.php',
            commands: __DIR__.'/../../routes/console.php',
            channels: __DIR__.'/../../routes/channels.php',
        );
    }

    public function register(): void
    {
        (new Access)->addControls([
            new GarmentControl,
            new WishlistItemControl,
        ]);
    }
}

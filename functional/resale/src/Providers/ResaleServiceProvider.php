<?php

namespace Functional\Resale\Providers;

use Functional\Resale\Access\Controls\VintedListingDraftControl;
use Functional\Resale\Contracts\ListingPublisher;
use Functional\Resale\Database\Seeders\ResaleSeeder;
use Functional\Resale\Listeners\CascadeGarmentListingDeletion;
use Functional\Resale\Listeners\CascadeUserResaleDeletion;
use Functional\Resale\Publishers\AssistedHandoff;
use Functional\Users\Models\User;
use Functional\Wardrobe\Models\Garment;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class ResaleServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'resale');

        User::deleting(CascadeUserResaleDeletion::class);
        Garment::deleting(CascadeGarmentListingDeletion::class);

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadSeeders([ResaleSeeder::class]);
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
            new VintedListingDraftControl,
        ]);

        $this->app->singleton(ListingPublisher::class, AssistedHandoff::class);
    }
}

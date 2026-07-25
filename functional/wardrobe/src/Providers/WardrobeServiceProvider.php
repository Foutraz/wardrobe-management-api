<?php

namespace Functional\Wardrobe\Providers;

use Functional\Users\Models\User;
use Functional\Wardrobe\Database\Seeders\WardrobeSeeder;
use Functional\Wardrobe\Listeners\CascadeGarmentDeletion;
use Functional\Wardrobe\Listeners\CascadeUserDeletion;
use Functional\Wardrobe\Models\Garment;
use Xefi\LaravelOSDD\LayerServiceProvider;

class WardrobeServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'wardrobe');

        User::deleting(CascadeUserDeletion::class);
        Garment::deleting(CascadeGarmentDeletion::class);

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadSeeders([WardrobeSeeder::class]);
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
        //
    }
}

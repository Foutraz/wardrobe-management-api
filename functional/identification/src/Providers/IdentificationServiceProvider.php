<?php

namespace Functional\Identification\Providers;

use Functional\Catalog\Events\ProductVariantMerged;
use Functional\Identification\Access\Controls\IdentificationRequestControl;
use Functional\Identification\Database\Seeders\IdentificationSeeder;
use Functional\Identification\Listeners\CascadeUserIdentificationDeletion;
use Functional\Identification\Listeners\ForgetGarmentOnIdentification;
use Functional\Identification\Listeners\RepointIdentificationsAfterVariantMerge;
use Functional\Users\Models\User;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Support\Facades\Event;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class IdentificationServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'identification');

        User::deleting(CascadeUserIdentificationDeletion::class);
        Garment::deleting(ForgetGarmentOnIdentification::class);

        Event::listen(ProductVariantMerged::class, RepointIdentificationsAfterVariantMerge::class);

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadSeeders([IdentificationSeeder::class]);
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
            new IdentificationRequestControl,
        ]);
    }
}

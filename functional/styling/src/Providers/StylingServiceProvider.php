<?php

namespace Functional\Styling\Providers;

use Functional\Styling\Access\Controls\OutfitControl;
use Functional\Styling\Database\Seeders\StylingSeeder;
use Functional\Styling\Listeners\AssignAuthenticatedOwner;
use Functional\Styling\Listeners\CascadeAvatarDeletion;
use Functional\Styling\Listeners\CascadeOutfitDeletion;
use Functional\Styling\Listeners\CascadeUserStylingDeletion;
use Functional\Styling\Listeners\GuardOutfitItemReference;
use Functional\Styling\Models\Avatar;
use Functional\Styling\Models\Outfit;
use Functional\Styling\Models\OutfitItem;
use Functional\Styling\Models\OutfitPlan;
use Functional\Users\Models\User;
use Lomkit\Access\Access;
use Xefi\LaravelOSDD\LayerServiceProvider;

class StylingServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'styling');

        OutfitItem::saving(GuardOutfitItemReference::class);

        Avatar::creating(AssignAuthenticatedOwner::class);
        Outfit::creating(AssignAuthenticatedOwner::class);
        OutfitPlan::creating(AssignAuthenticatedOwner::class);

        User::deleting(CascadeUserStylingDeletion::class);
        Outfit::deleting(CascadeOutfitDeletion::class);
        Avatar::deleting(CascadeAvatarDeletion::class);

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadSeeders([StylingSeeder::class]);
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
            new OutfitControl,
        ]);
    }
}

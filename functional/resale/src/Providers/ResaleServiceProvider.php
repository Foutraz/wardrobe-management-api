<?php

namespace Functional\Resale\Providers;

use Functional\Resale\Database\Seeders\ResaleSeeder;
use Xefi\LaravelOSDD\LayerServiceProvider;

class ResaleServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
            $this->loadSeeders([ResaleSeeder::class]);
        }

        $this->withRouting(
            web: __DIR__ . '/../../routes/web.php',
            api: __DIR__ . '/../../routes/api.php',
            commands: __DIR__ . '/../../routes/console.php',
            channels: __DIR__ . '/../../routes/channels.php',
        );
    }

    public function register(): void
    {
        //
    }
}

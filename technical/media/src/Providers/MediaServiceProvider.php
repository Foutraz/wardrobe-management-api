<?php

namespace Technical\Media\Providers;

use Technical\Media\Database\Seeders\MediaSeeder;
use Technical\Media\Models\Media;
use Xefi\LaravelOSDD\LayerServiceProvider;

class MediaServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadSeeders([MediaSeeder::class]);
        }
    }

    public function register(): void
    {
        config(['media-library.media_model' => Media::class]);
    }
}

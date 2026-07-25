<?php

namespace Technical\AiGateway\Providers;

use Technical\AiGateway\Contracts\CutoutDriver;
use Technical\AiGateway\Database\Seeders\AiGatewaySeeder;
use Technical\AiGateway\Drivers\RembgCutoutDriver;
use Technical\AiGateway\Drivers\UnavailableCutoutDriver;
use Xefi\LaravelOSDD\LayerServiceProvider;

class AiGatewayServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadSeeders([AiGatewaySeeder::class]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/ai-gateway.php', 'ai-gateway');

        $this->app->singleton(CutoutDriver::class, function (): CutoutDriver {
            return match (config('ai-gateway.cutout.driver')) {
                'rembg' => new RembgCutoutDriver(
                    (string) config('ai-gateway.cutout.rembg.binary'),
                    (int) config('ai-gateway.cutout.rembg.timeout'),
                ),
                default => new UnavailableCutoutDriver,
            };
        });
    }
}

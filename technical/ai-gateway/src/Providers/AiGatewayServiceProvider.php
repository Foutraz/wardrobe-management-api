<?php

namespace Technical\AiGateway\Providers;

use Technical\AiGateway\Contracts\CareLabelReader;
use Technical\AiGateway\Contracts\CutoutDriver;
use Technical\AiGateway\Database\Seeders\AiGatewaySeeder;
use Technical\AiGateway\Drivers\HttpCutoutDriver;
use Technical\AiGateway\Drivers\RembgCutoutDriver;
use Technical\AiGateway\Drivers\TesseractCareLabelReader;
use Technical\AiGateway\Drivers\UnavailableCareLabelReader;
use Technical\AiGateway\Drivers\UnavailableCutoutDriver;
use Technical\AiGateway\Support\CareLabelParser;
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
                'sidecar' => new HttpCutoutDriver(
                    (string) config('ai-gateway.sidecar.url'),
                    (int) config('ai-gateway.sidecar.timeout'),
                ),
                'rembg' => new RembgCutoutDriver(
                    (string) config('ai-gateway.cutout.rembg.binary'),
                    (int) config('ai-gateway.cutout.rembg.timeout'),
                ),
                default => new UnavailableCutoutDriver,
            };
        });

        $this->app->singleton(CareLabelReader::class, function (): CareLabelReader {
            return match (config('ai-gateway.care_label.driver')) {
                'tesseract' => new TesseractCareLabelReader(
                    new CareLabelParser,
                    (string) config('ai-gateway.care_label.tesseract.binary'),
                    (int) config('ai-gateway.care_label.tesseract.timeout'),
                ),
                default => new UnavailableCareLabelReader,
            };
        });
    }
}

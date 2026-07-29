<?php

namespace Technical\AiGateway\Drivers;

use Illuminate\Support\Facades\Http;
use Technical\AiGateway\Contracts\CutoutDriver;
use Technical\AiGateway\Values\OperationOutcome;

class HttpCutoutDriver implements CutoutDriver
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds,
    ) {}

    public function name(): string
    {
        return 'sidecar-rembg';
    }

    /**
     * Ask the sidecar whether it is up, so an unreachable service is reported as unavailable
     * rather than as a failed cutout.
     */
    public function isAvailable(): bool
    {
        return Http::timeout(3)->get($this->baseUrl.'/health')->successful();
    }

    public function removeBackground(string $sourcePath, string $destinationPath): OperationOutcome
    {
        $response = Http::timeout($this->timeoutSeconds)
            ->attach('image', (string) file_get_contents($sourcePath), basename($sourcePath))
            ->post($this->baseUrl.'/cutout');

        if ($response->failed()) {
            return OperationOutcome::failed(sprintf(
                'The cutout sidecar answered %d.',
                $response->status(),
            ));
        }

        file_put_contents($destinationPath, $response->body());

        return OperationOutcome::succeeded();
    }
}

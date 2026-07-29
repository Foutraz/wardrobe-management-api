<?php

namespace Technical\AiGateway\Drivers;

use Illuminate\Support\Facades\Http;
use Technical\AiGateway\Contracts\CareLabelReader;
use Technical\AiGateway\Support\CareLabelParser;
use Technical\AiGateway\Values\AttributeReading;

class HttpCareLabelReader implements CareLabelReader
{
    public function __construct(
        private readonly CareLabelParser $parser,
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds,
    ) {}

    public function name(): string
    {
        return 'sidecar-tesseract';
    }

    /**
     * Ask the sidecar whether it is up, so an unreachable service is reported as unavailable
     * rather than as an unreadable label.
     */
    public function isAvailable(): bool
    {
        return Http::timeout(3)->get($this->baseUrl.'/health')->successful();
    }

    public function read(string $imagePath): AttributeReading
    {
        $response = Http::timeout($this->timeoutSeconds)
            ->attach('image', (string) file_get_contents($imagePath), basename($imagePath))
            ->post($this->baseUrl.'/ocr');

        if ($response->failed()) {
            return AttributeReading::failed(sprintf(
                'The OCR sidecar answered %d.',
                $response->status(),
            ));
        }

        $attributes = $this->parser->parse((string) $response->json('text'));

        if ($attributes === []) {
            return AttributeReading::failed('The label was read but nothing recognisable was printed on it.');
        }

        return AttributeReading::succeeded($attributes, (int) round(count($attributes) / 3 * 100));
    }
}

<?php

namespace Technical\AiGateway\Drivers;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Technical\AiGateway\Contracts\CareLabelReader;
use Technical\AiGateway\Support\CareLabelParser;
use Technical\AiGateway\Values\AttributeReading;

class TesseractCareLabelReader implements CareLabelReader
{
    public function __construct(
        private readonly CareLabelParser $parser,
        private readonly string $binary,
        private readonly int $timeoutSeconds,
    ) {}

    public function name(): string
    {
        return 'tesseract';
    }

    /**
     * Look for the tesseract executable rather than assuming the OCR toolchain is installed.
     */
    public function isAvailable(): bool
    {
        return (new ExecutableFinder)->find($this->binary) !== null;
    }

    public function read(string $imagePath): AttributeReading
    {
        $process = new Process([$this->binary, $imagePath, 'stdout']);
        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (! $process->isSuccessful()) {
            return AttributeReading::failed(trim($process->getErrorOutput()) ?: 'tesseract exited with a non-zero status');
        }

        $attributes = $this->parser->parse($process->getOutput());

        if ($attributes === []) {
            return AttributeReading::failed('The label was read but nothing recognisable was printed on it.');
        }

        return AttributeReading::succeeded($attributes, $this->confidenceFor($attributes));
    }

    /**
     * Rate the reading by how many of the three facts a label can state were actually found.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function confidenceFor(array $attributes): int
    {
        return (int) round(count($attributes) / 3 * 100);
    }
}

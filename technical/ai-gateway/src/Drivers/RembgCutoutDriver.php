<?php

namespace Technical\AiGateway\Drivers;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Technical\AiGateway\Contracts\CutoutDriver;
use Technical\AiGateway\Values\OperationOutcome;

class RembgCutoutDriver implements CutoutDriver
{
    public function __construct(
        private readonly string $binary,
        private readonly int $timeoutSeconds,
    ) {}

    public function name(): string
    {
        return 'rembg';
    }

    /**
     * Look for the rembg executable rather than assuming the Python toolchain is installed.
     */
    public function isAvailable(): bool
    {
        return (new ExecutableFinder)->find($this->binary) !== null;
    }

    public function removeBackground(string $sourcePath, string $destinationPath): OperationOutcome
    {
        $process = new Process([$this->binary, 'i', $sourcePath, $destinationPath]);
        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (! $process->isSuccessful()) {
            return OperationOutcome::failed(trim($process->getErrorOutput()) ?: 'rembg exited with a non-zero status');
        }

        if (! is_file($destinationPath)) {
            return OperationOutcome::failed('rembg reported success but wrote no file');
        }

        return OperationOutcome::succeeded();
    }
}

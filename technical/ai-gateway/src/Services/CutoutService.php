<?php

namespace Technical\AiGateway\Services;

use Illuminate\Database\Eloquent\Model;
use Technical\AiGateway\Contracts\CutoutDriver;
use Technical\AiGateway\Enums\AiOperationKind;
use Technical\AiGateway\Values\OperationOutcome;

class CutoutService
{
    public function __construct(
        private readonly CutoutDriver $driver,
        private readonly AiOperationJournal $journal,
    ) {}

    /**
     * Strip the background from an image, recording the attempt whether or not it worked.
     */
    public function removeBackground(
        string $sourcePath,
        string $destinationPath,
        ?Model $subject = null,
        ?string $userId = null,
    ): OperationOutcome {
        $startedAt = hrtime(true);

        $outcome = $this->attempt($sourcePath, $destinationPath);

        $latencyMs = (int) ((hrtime(true) - $startedAt) / 1_000_000);

        $this->journal->record(
            AiOperationKind::GarmentCutout,
            $this->driver->name(),
            $outcome,
            $latencyMs,
            $subject,
            $userId,
        );

        return $outcome;
    }

    /**
     * Run the driver and hold it to the contract, so the journal records what really happened.
     */
    private function attempt(string $sourcePath, string $destinationPath): OperationOutcome
    {
        if (! $this->driver->isAvailable()) {
            return OperationOutcome::unavailable(
                sprintf('The %s cutout driver is not available on this machine.', $this->driver->name()),
            );
        }

        $outcome = $this->driver->removeBackground($sourcePath, $destinationPath);

        if (! $outcome->status->producedOutput()) {
            return $outcome;
        }

        if (! is_file($destinationPath) || mime_content_type($destinationPath) !== 'image/png') {
            return OperationOutcome::failed(
                sprintf('The %s driver claimed success but returned no PNG.', $this->driver->name()),
                $outcome->costCents,
            );
        }

        return $outcome;
    }
}

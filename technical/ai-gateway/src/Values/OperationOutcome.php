<?php

namespace Technical\AiGateway\Values;

use Technical\AiGateway\Enums\AiOperationStatus;

final readonly class OperationOutcome
{
    private function __construct(
        public AiOperationStatus $status,
        public ?string $failureReason,
        public int $costCents,
    ) {}

    /**
     * Report an operation that produced its output.
     */
    public static function succeeded(int $costCents = 0): self
    {
        return new self(AiOperationStatus::Succeeded, null, $costCents);
    }

    /**
     * Report a provider that was reachable but could not produce an output.
     */
    public static function failed(string $reason, int $costCents = 0): self
    {
        return new self(AiOperationStatus::Failed, $reason, $costCents);
    }

    /**
     * Report a provider that is not configured or not installed on this machine.
     */
    public static function unavailable(string $reason): self
    {
        return new self(AiOperationStatus::Unavailable, $reason, 0);
    }
}

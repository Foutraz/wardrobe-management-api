<?php

namespace Technical\AiGateway\Values;

use Technical\AiGateway\Enums\AiOperationStatus;

final readonly class AttributeReading
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    private function __construct(
        public AiOperationStatus $status,
        public array $attributes,
        public ?int $confidence,
        public ?string $failureReason,
        public int $costCents,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function succeeded(array $attributes, int $confidence, int $costCents = 0): self
    {
        return new self(AiOperationStatus::Succeeded, $attributes, $confidence, null, $costCents);
    }

    public static function failed(string $reason, int $costCents = 0): self
    {
        return new self(AiOperationStatus::Failed, [], null, $reason, $costCents);
    }

    public static function unavailable(string $reason): self
    {
        return new self(AiOperationStatus::Unavailable, [], null, $reason, 0);
    }

    /**
     * Convert the reading into the outcome shape the operations journal records.
     */
    public function toOutcome(): OperationOutcome
    {
        return match ($this->status) {
            AiOperationStatus::Succeeded => OperationOutcome::succeeded($this->costCents),
            AiOperationStatus::Failed => OperationOutcome::failed((string) $this->failureReason, $this->costCents),
            AiOperationStatus::Unavailable => OperationOutcome::unavailable((string) $this->failureReason),
        };
    }
}

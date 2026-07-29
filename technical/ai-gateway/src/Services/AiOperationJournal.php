<?php

namespace Technical\AiGateway\Services;

use Illuminate\Database\Eloquent\Model;
use Technical\AiGateway\Enums\AiOperationKind;
use Technical\AiGateway\Models\AiOperation;
use Technical\AiGateway\Values\OperationOutcome;

class AiOperationJournal
{
    /**
     * Record what a provider was asked to do, what it cost and how long it took.
     */
    public function record(
        AiOperationKind $kind,
        string $provider,
        OperationOutcome $outcome,
        int $latencyMs,
        ?Model $subject = null,
        ?string $userId = null,
    ): AiOperation {
        return AiOperation::create([
            'user_id' => $userId,
            'kind' => $kind,
            'provider' => $provider,
            'status' => $outcome->status,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'cost_cents' => $outcome->costCents,
            'latency_ms' => $latencyMs,
            'failure_reason' => $outcome->failureReason,
        ]);
    }
}

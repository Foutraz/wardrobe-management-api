<?php

namespace Technical\AiGateway\Enums;

enum AiOperationStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Unavailable = 'unavailable';

    /**
     * Determine whether the operation produced a usable result.
     */
    public function producedOutput(): bool
    {
        return $this === self::Succeeded;
    }

    /**
     * Determine whether retrying could plausibly succeed without a configuration change.
     */
    public function isWorthRetrying(): bool
    {
        return $this === self::Failed;
    }
}

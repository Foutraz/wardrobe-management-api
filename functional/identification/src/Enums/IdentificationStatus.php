<?php

namespace Functional\Identification\Enums;

enum IdentificationStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Confirmed = 'confirmed';

    /**
     * Determine whether the request carries attributes the owner can review.
     */
    public function hasAttributes(): bool
    {
        return in_array($this, [self::Succeeded, self::Confirmed], true);
    }

    /**
     * Determine whether the owner may still turn this request into a garment.
     */
    public function awaitsConfirmation(): bool
    {
        return $this === self::Succeeded;
    }

    /**
     * Get the states this one may legally move to.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Processing, self::Failed],
            self::Processing => [self::Succeeded, self::Failed],
            self::Succeeded => [self::Confirmed],
            self::Failed => [self::Pending],
            self::Confirmed => [],
        };
    }

    /**
     * Determine whether moving to the given state is legal.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}

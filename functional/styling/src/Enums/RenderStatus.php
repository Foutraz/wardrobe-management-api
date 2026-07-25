<?php

namespace Functional\Styling\Enums;

enum RenderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    /**
     * Determine whether an image is available for this render.
     */
    public function hasImage(): bool
    {
        return $this === self::Succeeded;
    }

    /**
     * Determine whether the render is still on its way to an outcome.
     */
    public function isSettled(): bool
    {
        return in_array($this, [self::Succeeded, self::Failed], true);
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
            self::Failed => [self::Pending],
            self::Succeeded => [],
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

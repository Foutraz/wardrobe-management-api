<?php

namespace Functional\Resale\Enums;

enum VintedDraftStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case HandedOff = 'handed_off';
    case PublishedByUser = 'published_by_user';
    case Abandoned = 'abandoned';

    /**
     * Determine whether the draft is still ours to edit.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Ready], true);
    }

    /**
     * Determine whether the draft has left our hands for the seller to publish.
     */
    public function isWithTheSeller(): bool
    {
        return in_array($this, [self::HandedOff, self::PublishedByUser], true);
    }

    /**
     * Determine whether nothing more will happen to this draft.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::PublishedByUser, self::Abandoned], true);
    }

    /**
     * Get the states this one may legally move to.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Ready, self::Abandoned],
            self::Ready => [self::Draft, self::HandedOff, self::Abandoned],
            self::HandedOff => [self::PublishedByUser, self::Abandoned],
            self::PublishedByUser, self::Abandoned => [],
        };
    }

    /**
     * Determine whether moving to the given state is legal.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Get the translated label for this state.
     */
    public function label(): string
    {
        return __('resale::draft_status.'.$this->value);
    }
}

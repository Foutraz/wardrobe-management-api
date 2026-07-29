<?php

namespace Functional\Styling\Values;

use Functional\Styling\Enums\PreviewMode;

final readonly class PreviewCacheKey
{
    private function __construct(
        public string $value,
    ) {}

    /**
     * Build the key that lets an already rendered preview be found instead of paid for again.
     *
     * @param  list<string>  $itemIds
     */
    public static function for(PreviewMode $mode, ?string $avatarVersionId, array $itemIds): self
    {
        $orderedItemIds = array_values(array_unique($itemIds));
        sort($orderedItemIds);

        return new self(hash('sha256', implode('|', [
            $mode->value,
            $avatarVersionId ?? 'no-avatar',
            implode(',', $orderedItemIds),
        ])));
    }
}

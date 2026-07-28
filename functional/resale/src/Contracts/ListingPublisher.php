<?php

namespace Functional\Resale\Contracts;

use Functional\Resale\Models\VintedListingDraft;
use Functional\Resale\Values\HandoffInstructions;

interface ListingPublisher
{
    /**
     * Get the publisher name recorded against a handed-off draft.
     */
    public function name(): string;

    /**
     * Hand the finished draft over so it can reach Vinted.
     */
    public function publish(VintedListingDraft $draft): HandoffInstructions;
}

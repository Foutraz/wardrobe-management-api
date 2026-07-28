<?php

namespace Functional\Resale\Publishers;

use Functional\Resale\Contracts\ListingPublisher;
use Functional\Resale\Models\VintedListingDraft;
use Functional\Resale\Values\HandoffInstructions;
use Functional\Wardrobe\Enums\GarmentMediaCollection;

class AssistedHandoff implements ListingPublisher
{
    public function name(): string
    {
        return 'assisted-handoff';
    }

    /**
     * Hand the seller everything they need to publish the listing themselves.
     */
    public function publish(VintedListingDraft $draft): HandoffInstructions
    {
        $garment = $draft->garment;

        $photoUrls = array_values(
            $garment
                ->getMedia(GarmentMediaCollection::Cutouts->value)
                ->merge($garment->getMedia(GarmentMediaCollection::Photos->value))
                ->map(fn ($media): string => $media->getFullUrl())
                ->all(),
        );

        return new HandoffInstructions(
            publisher: $this->name(),
            target: 'https://www.vinted.fr/items/new',
            photoUrls: $photoUrls,
            guidance: __('resale::handoff.guidance'),
        );
    }
}

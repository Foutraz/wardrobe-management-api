<?php

namespace Functional\Resale\Services;

use Functional\Resale\Enums\VintedDraftStatus;
use Functional\Resale\Exceptions\GarmentIsNotSellable;
use Functional\Resale\Models\VintedListingDraft;
use Functional\Resale\Values\SuggestedPrice;
use Functional\Wardrobe\Models\Garment;

class ListingDraftComposer
{
    /**
     * Turn what we already know about a garment into a listing the seller only has to review.
     *
     * @throws GarmentIsNotSellable
     */
    public function compose(Garment $garment): VintedListingDraft
    {
        if (! $garment->availability_status->isSellable()) {
            throw GarmentIsNotSellable::because($garment->availability_status);
        }

        $existing = VintedListingDraft::query()
            ->where('garment_id', $garment->getKey())
            ->whereIn('status', [VintedDraftStatus::Draft, VintedDraftStatus::Ready])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $price = SuggestedPrice::from(
            $garment->purchase_price_cents,
            $garment->purchase_currency,
            $garment->condition,
        );

        $draft = new VintedListingDraft;

        $draft->forceFill([
            'user_id' => $garment->user_id,
            'garment_id' => $garment->getKey(),
            'title' => $this->title($garment),
            'description' => $this->description($garment),
            'brand_label' => $garment->brand?->name,
            'size_label' => $garment->size_label,
            'colour_label' => $garment->colour_name,
            'condition_label' => $garment->condition->label(),
            'price_cents' => $price->cents,
            'currency' => $price->currency,
            'status' => VintedDraftStatus::Draft,
        ])->save();

        return $draft;
    }

    /**
     * Build a title from the brand, the garment name and the size, in that order of usefulness.
     */
    private function title(Garment $garment): string
    {
        return implode(' ', array_filter([
            $garment->brand?->name,
            $garment->name,
            $garment->size_label,
        ]));
    }

    /**
     * Build a description from every attribute worth stating, one line each.
     */
    private function description(Garment $garment): string
    {
        $lines = array_filter([
            $garment->name,
            $garment->brand?->name === null ? null : __('resale::listing.brand', ['brand' => $garment->brand->name]),
            $garment->size_label === null ? null : __('resale::listing.size', ['size' => $garment->size_label]),
            $garment->colour_name === null ? null : __('resale::listing.colour', ['colour' => $garment->colour_name]),
            $garment->material_composition === null ? null : __('resale::listing.material', ['material' => $garment->material_composition]),
            __('resale::listing.condition', ['condition' => $garment->condition->label()]),
        ]);

        return implode(PHP_EOL, $lines);
    }
}

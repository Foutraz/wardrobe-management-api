<?php

namespace Functional\Wardrobe\Services;

use Functional\Wardrobe\Enums\GarmentAvailability;
use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WearEvent;
use Functional\Wardrobe\Values\CostPerWear;
use Functional\Wardrobe\Values\WardrobeTotals;
use Illuminate\Support\Facades\DB;

class WardrobeStatistics
{
    private const RANKING_SIZE = 5;

    /**
     * Summarise a wardrobe, letting the database do every aggregation.
     *
     * @return array<string, mixed>
     */
    public function for(string $userId): array
    {
        $totals = $this->totals($userId);
        $wearCount = $this->wearCount($userId);

        return [
            'garment_count' => $totals->garmentCount,
            'available_count' => $totals->availableCount,
            'total_spend_cents' => $totals->totalSpendCents,
            'priced_garment_count' => $totals->pricedGarmentCount,
            'wear_count' => $wearCount,
            'never_worn_count' => $this->neverWornCount($userId),
            'wardrobe_cost_per_wear_cents' => (new CostPerWear($totals->totalSpendCents, $wearCount))->cents(),
            'most_worn' => $this->ranking($userId, 'desc'),
            'least_worn' => $this->ranking($userId, 'asc'),
            'by_category' => $this->byCategory($userId),
        ];
    }

    /**
     * Get the counts and the spend in one pass over the wardrobe.
     */
    private function totals(string $userId): WardrobeTotals
    {
        $row = Garment::query()
            ->where('user_id', $userId)
            ->selectRaw('count(*) as garment_count')
            ->selectRaw('coalesce(sum(purchase_price_cents), 0) as total_spend_cents')
            ->selectRaw('count(purchase_price_cents) as priced_garment_count')
            ->selectRaw('sum(case when availability_status = ? then 1 else 0 end) as available_count', [
                GarmentAvailability::Available->value,
            ])
            ->toBase()
            ->first();

        return WardrobeTotals::fromRow((array) $row);
    }

    /**
     * Count every wear recorded across the wardrobe.
     */
    private function wearCount(string $userId): int
    {
        return WearEvent::query()
            ->whereIn('garment_id', Garment::query()->where('user_id', $userId)->select('id'))
            ->count();
    }

    /**
     * Count garments that have never been worn, the sharpest signal of a stale wardrobe.
     */
    private function neverWornCount(string $userId): int
    {
        return Garment::query()
            ->where('user_id', $userId)
            ->whereDoesntHave('wearEvents')
            ->count();
    }

    /**
     * Rank garments by how often they have been worn.
     *
     * @param  'asc'|'desc'  $direction
     * @return list<array<string, mixed>>
     */
    private function ranking(string $userId, string $direction): array
    {
        $ranked = Garment::query()
            ->where('user_id', $userId)
            ->withCount('wearEvents')
            ->orderBy('wear_events_count', $direction)
            ->orderBy('name')
            ->limit(self::RANKING_SIZE)
            ->get()
            ->map(fn (Garment $garment): array => [
                'id' => $garment->getKey(),
                'name' => $garment->name,
                'wear_count' => $garment->wear_events_count,
                'cost_per_wear_cents' => $garment->costPerWear()->cents(),
            ])
            ->all();

        return array_values($ranked);
    }

    /**
     * Break the wardrobe down by category, so gaps become visible.
     *
     * @return list<array<string, mixed>>
     */
    private function byCategory(string $userId): array
    {
        $breakdown = Garment::query()
            ->where('garments.user_id', $userId)
            ->join('categories', 'categories.id', '=', 'garments.category_id')
            ->groupBy('categories.id', 'categories.slug')
            ->select('categories.slug')
            ->selectRaw('count(*) as garment_count')
            ->orderByDesc(DB::raw('count(*)'))
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'slug' => $row->slug,
                'label' => __('catalog::category.'.$row->slug),
                'garment_count' => (int) $row->garment_count,
            ])
            ->all();

        return array_values($breakdown);
    }
}

<?php

namespace Functional\Resale\Values;

use Functional\Wardrobe\Enums\GarmentCondition;

final readonly class SuggestedPrice
{
    private const FLOOR_CENTS = 300;

    private function __construct(
        public int $cents,
        public string $currency,
    ) {}

    /**
     * Suggest a second-hand asking price from what the garment cost and how worn it is.
     */
    public static function from(?int $purchasePriceCents, ?string $currency, GarmentCondition $condition): self
    {
        $resolvedCurrency = $currency ?? 'EUR';

        if ($purchasePriceCents === null || $purchasePriceCents === 0) {
            return new self(self::FLOOR_CENTS, $resolvedCurrency);
        }

        $suggested = (int) round($purchasePriceCents * self::ratioFor($condition));

        return new self(max($suggested, self::FLOOR_CENTS), $resolvedCurrency);
    }

    /**
     * Get the share of the purchase price a garment in this condition tends to fetch.
     */
    private static function ratioFor(GarmentCondition $condition): float
    {
        return match ($condition) {
            GarmentCondition::NewWithTag => 0.70,
            GarmentCondition::NewWithoutTag => 0.60,
            GarmentCondition::VeryGood => 0.45,
            GarmentCondition::Good => 0.30,
            GarmentCondition::Satisfactory => 0.20,
        };
    }
}

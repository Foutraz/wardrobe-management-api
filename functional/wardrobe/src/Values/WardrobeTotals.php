<?php

namespace Functional\Wardrobe\Values;

final readonly class WardrobeTotals
{
    public function __construct(
        public int $garmentCount,
        public int $availableCount,
        public int $totalSpendCents,
        public int $pricedGarmentCount,
    ) {}

    /**
     * Build the totals from an aggregate row, casting because MySQL returns sums as strings.
     *
     * @param  array<string, mixed>  $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            garmentCount: (int) ($row['garment_count'] ?? 0),
            availableCount: (int) ($row['available_count'] ?? 0),
            totalSpendCents: (int) ($row['total_spend_cents'] ?? 0),
            pricedGarmentCount: (int) ($row['priced_garment_count'] ?? 0),
        );
    }
}

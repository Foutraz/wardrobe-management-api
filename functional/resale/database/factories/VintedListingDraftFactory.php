<?php

namespace Functional\Resale\Database\Factories;

use Functional\Resale\Enums\VintedDraftStatus;
use Functional\Resale\Models\VintedListingDraft;
use Functional\Users\Models\User;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends Factory<VintedListingDraft>
 */
class VintedListingDraftFactory extends Factory
{
    protected $model = VintedListingDraft::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'garment_id' => Garment::factory(),
            'title' => Str::title(faker()->words(3)),
            'description' => faker()->sentences(2),
            'brand_label' => Str::title(faker()->words(1)),
            'size_label' => Arr::random(['XS', 'S', 'M', 'L', 'XL']),
            'colour_label' => faker()->colorName(),
            'condition_label' => 'Très bon état',
            'price_cents' => faker()->number(500, 8000),
            'currency' => 'EUR',
            'status' => VintedDraftStatus::Draft,
        ];
    }

    /**
     * Present the draft as reviewed and ready for the seller to publish.
     */
    public function ready(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => VintedDraftStatus::Ready]);
    }

    /**
     * Present the draft as already handed over to the seller.
     */
    public function handedOff(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VintedDraftStatus::HandedOff,
            'handed_off_at' => now(),
        ]);
    }
}

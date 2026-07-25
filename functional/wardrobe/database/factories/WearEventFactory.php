<?php

namespace Functional\Wardrobe\Database\Factories;

use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WearEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WearEvent>
 */
class WearEventFactory extends Factory
{
    protected $model = WearEvent::class;

    public function definition(): array
    {
        return [
            'garment_id' => Garment::factory(),
            'worn_on' => now()->subDays(faker()->number(0, 365))->toDateString(),
        ];
    }
}

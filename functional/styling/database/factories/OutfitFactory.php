<?php

namespace Functional\Styling\Database\Factories;

use Functional\Styling\Enums\Season;
use Functional\Styling\Models\Outfit;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends Factory<Outfit>
 */
class OutfitFactory extends Factory
{
    protected $model = Outfit::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => Str::title(faker()->words(2)),
            'occasion' => Arr::random(['work', 'date night', 'weekend', 'wedding', 'travel']),
            'season' => Arr::random(Season::cases()),
            'notes' => null,
        ];
    }
}

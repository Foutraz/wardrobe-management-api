<?php

namespace Functional\Styling\Database\Factories;

use Functional\Styling\Models\Outfit;
use Functional\Styling\Models\OutfitPlan;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutfitPlan>
 */
class OutfitPlanFactory extends Factory
{
    protected $model = OutfitPlan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'outfit_id' => Outfit::factory(),
            'scheduled_for' => now()->addDays(faker()->number(1, 30))->toDateString(),
        ];
    }
}

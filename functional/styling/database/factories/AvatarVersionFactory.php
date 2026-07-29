<?php

namespace Functional\Styling\Database\Factories;

use Functional\Styling\Models\Avatar;
use Functional\Styling\Models\AvatarVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<AvatarVersion>
 */
class AvatarVersionFactory extends Factory
{
    protected $model = AvatarVersion::class;

    public function definition(): array
    {
        return [
            'avatar_id' => Avatar::factory(),
            'version' => 1,
            'parameters' => [
                'height_cm' => faker()->number(150, 200),
                'build' => Arr::random(['slim', 'average', 'athletic', 'curvy']),
                'skin_tone' => faker()->hexColor(),
            ],
        ];
    }
}

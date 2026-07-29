<?php

namespace Functional\Styling\Database\Factories;

use Functional\Styling\Models\Avatar;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Avatar>
 */
class AvatarFactory extends Factory
{
    protected $model = Avatar::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => Str::title(faker()->words(2)),
        ];
    }
}

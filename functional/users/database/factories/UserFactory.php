<?php

namespace Functional\Users\Database\Factories;

use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => faker()->name(),
            'email' => Str::lower(faker()->ulid()).'@example.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'locale' => 'fr',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Leave the account with an unconfirmed email address.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => ['email_verified_at' => null]);
    }
}

<?php

namespace Functional\Users\Database\Seeders;

use Functional\Users\Enums\Ability;
use Functional\Users\Enums\UserRole;
use Functional\Users\Models\Permission;
use Functional\Users\Models\Role;
use Functional\Users\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Ability::cases() as $ability) {
            Permission::findOrCreate($ability->value);
        }

        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value)->syncPermissions(
                array_map(fn (Ability $ability): string => $ability->value, $role->abilities()),
            );
        }

        User::firstOrCreate(
            ['email' => 'member@example.test'],
            ['name' => 'Member', 'password' => Hash::make('password'), 'locale' => 'fr'],
        )->assignRole(UserRole::Member->value);

        User::firstOrCreate(
            ['email' => 'moderator@example.test'],
            ['name' => 'Moderator', 'password' => Hash::make('password'), 'locale' => 'fr'],
        )->assignRole(UserRole::Moderator->value);
    }
}

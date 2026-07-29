<?php

namespace Tests\Support;

use Functional\Users\Enums\Ability;
use Functional\Users\Models\Permission;
use Functional\Users\Models\User;
use Spatie\Permission\PermissionRegistrar;

trait MemberAccount
{
    /**
     * Build an account holding the abilities an ordinary member is granted.
     */
    protected function member(): User
    {
        foreach (Ability::cases() as $ability) {
            Permission::findOrCreate($ability->value);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();

        $user->givePermissionTo(
            array_map(fn (Ability $ability): string => $ability->value, Ability::forMembers()),
        );

        return $user->fresh();
    }
}

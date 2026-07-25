<?php

namespace Functional\Users\Providers;

use Functional\Users\Database\Seeders\UsersSeeder;
use Functional\Users\Models\Permission;
use Functional\Users\Models\PersonalAccessToken;
use Functional\Users\Models\Role;
use Functional\Users\Models\User;
use Laravel\Sanctum\Sanctum;
use Xefi\LaravelOSDD\LayerServiceProvider;

class UsersServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadSeeders([UsersSeeder::class]);
        }
    }

    public function register(): void
    {
        config([
            'auth.providers.users.model' => User::class,
            'permission.models.permission' => Permission::class,
            'permission.models.role' => Role::class,
        ]);
    }
}

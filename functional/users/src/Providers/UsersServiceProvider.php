<?php

namespace Functional\Users\Providers;

use Functional\Users\Database\Seeders\UsersSeeder;
use Functional\Users\Http\Middleware\SetLocaleFromUser;
use Functional\Users\Models\Permission;
use Functional\Users\Models\PersonalAccessToken;
use Functional\Users\Models\Role;
use Functional\Users\Models\User;
use Functional\Users\Support\TokenIdleWindow;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Xefi\LaravelOSDD\LayerServiceProvider;

class UsersServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Sanctum::authenticateAccessTokensUsing(
            fn (PersonalAccessToken $token, bool $isValid): bool => $isValid
                && app(TokenIdleWindow::class)->isStillFresh($token),
        );

        Route::aliasMiddleware('locale', SetLocaleFromUser::class);

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->loadSeeders([UsersSeeder::class]);
        }

        $this->withRouting(
            api: __DIR__.'/../../routes/api.php',
        );
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/users.php', 'users');

        config([
            'auth.providers.users.model' => User::class,
            'permission.models.permission' => Permission::class,
            'permission.models.role' => Role::class,
        ]);
    }
}

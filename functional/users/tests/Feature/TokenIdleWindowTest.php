<?php

namespace Functional\Users\Tests\Feature;

use Functional\Users\Enums\Ability;
use Functional\Users\Enums\UserRole;
use Functional\Users\Models\Permission;
use Functional\Users\Models\PersonalAccessToken;
use Functional\Users\Models\Role;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TokenIdleWindowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_token_left_idle_past_the_window_stops_working(): void
    {
        $token = $this->tokenForFreshAccount();

        $this->travel(21)->minutes();

        $this->withToken($token)
            ->postJson('/api/v1/garments/search', ['search' => []])
            ->assertUnauthorized();
    }

    public function test_a_token_used_inside_the_window_keeps_working(): void
    {
        $token = $this->tokenForFreshAccount();

        $this->travel(19)->minutes();

        $this->withToken($token)
            ->postJson('/api/v1/garments/search', ['search' => []])
            ->assertOk();
    }

    public function test_activity_slides_the_window_forward(): void
    {
        $token = $this->tokenForFreshAccount();

        foreach ([15, 15, 15] as $minutes) {
            $this->travel($minutes)->minutes();

            $this->withToken($token)
                ->postJson('/api/v1/garments/search', ['search' => []])
                ->assertOk();
        }

        $this->assertGreaterThan(40, now()->diffInMinutes($this->firstTokenCreatedAt(), true));
    }

    public function test_refreshing_returns_a_new_token_and_retires_the_old_one(): void
    {
        $token = $this->tokenForFreshAccount();

        $refreshed = $this->withToken($token)
            ->postJson('/api/v1/token/refresh')
            ->assertOk()
            ->json('data.token');

        $this->assertNotSame($token, $refreshed);
        $this->assertSame(1, PersonalAccessToken::query()->count(), 'the old token must be gone from the store');

        $this->forgetResolvedUser();

        $this->withToken($refreshed)
            ->postJson('/api/v1/garments/search', ['search' => []])
            ->assertOk();

        $this->forgetResolvedUser();

        $this->withToken($token)
            ->postJson('/api/v1/garments/search', ['search' => []])
            ->assertUnauthorized();
    }

    public function test_an_idle_token_cannot_be_refreshed(): void
    {
        $token = $this->tokenForFreshAccount();

        $this->travel(21)->minutes();

        $this->withToken($token)->postJson('/api/v1/token/refresh')->assertUnauthorized();
    }

    public function test_logging_out_retires_only_the_token_it_arrived_with(): void
    {
        $user = $this->member();
        $phone = $user->createToken('phone')->plainTextToken;
        $laptop = $user->createToken('laptop')->plainTextToken;

        $this->withToken($phone)->postJson('/api/v1/logout')->assertNoContent();

        $this->assertSame(1, PersonalAccessToken::query()->count(), 'only the phone token must be gone');

        $this->forgetResolvedUser();
        $this->withToken($phone)->postJson('/api/v1/garments/search', ['search' => []])->assertUnauthorized();

        $this->forgetResolvedUser();
        $this->withToken($laptop)->postJson('/api/v1/garments/search', ['search' => []])->assertOk();
    }

    /**
     * Drop the user the guard cached, since a real request would resolve it from scratch.
     */
    private function forgetResolvedUser(): void
    {
        $this->app['auth']->forgetGuards();
    }

    /**
     * Get the creation moment of the earliest token, to prove the window slid rather than reset.
     */
    private function firstTokenCreatedAt(): Carbon
    {
        return PersonalAccessToken::query()->orderBy('created_at')->firstOrFail()->created_at;
    }

    /**
     * Register an account through the API and return its bearer token.
     */
    private function tokenForFreshAccount(): string
    {
        $this->seedRoles();

        return (string) $this->postJson('/api/v1/register', [
            'name' => 'Quentin',
            'email' => 'quentin@example.test',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ])->json('data.token');
    }

    /**
     * Build an account holding the member abilities without going through the API.
     */
    private function member(): User
    {
        $this->seedRoles();

        return User::factory()
            ->create(['password' => Hash::make('correct-horse-battery-staple')])
            ->assignRole(UserRole::Member->value)
            ->fresh();
    }

    /**
     * Seed the roles a registration assigns.
     */
    private function seedRoles(): void
    {
        foreach (Ability::cases() as $ability) {
            Permission::findOrCreate($ability->value);
        }

        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value)->syncPermissions(
                array_map(fn (Ability $ability): string => $ability->value, $role->abilities()),
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

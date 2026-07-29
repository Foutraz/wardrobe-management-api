<?php

namespace Functional\Users\Tests\Feature;

use Functional\Users\Enums\Ability;
use Functional\Users\Enums\Locale;
use Functional\Users\Enums\UserRole;
use Functional\Users\Models\Permission;
use Functional\Users\Models\Role;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
    }

    public function test_registering_opens_an_account_and_returns_a_token(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Quentin',
            'email' => 'quentin@example.test',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ])
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'quentin@example.test')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.idle_timeout_minutes', 20);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertNotNull($response->json('data.expires_at'));
    }

    public function test_a_new_account_can_immediately_use_its_token(): void
    {
        $token = $this->postJson('/api/v1/register', [
            'name' => 'Quentin',
            'email' => 'quentin@example.test',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ])->json('data.token');

        $this->withToken($token)
            ->postJson('/api/v1/garments/search', ['search' => []])
            ->assertOk();
    }

    public function test_a_new_account_holds_the_member_abilities(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Quentin',
            'email' => 'quentin@example.test',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ])->assertCreated();

        $user = User::query()->where('email', 'quentin@example.test')->firstOrFail();

        $this->assertTrue($user->can(Ability::ViewGarment->value));
        $this->assertFalse($user->can(Ability::ModerateCatalog->value));
    }

    public function test_registering_defaults_to_french(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Quentin',
            'email' => 'quentin@example.test',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ])->assertJsonPath('data.user.locale', Locale::French->value);
    }

    public function test_it_refuses_an_email_already_taken(): void
    {
        User::factory()->create(['email' => 'quentin@example.test']);

        $this->postJson('/api/v1/register', [
            'name' => 'Quentin',
            'email' => 'quentin@example.test',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ])->assertUnprocessable()->assertJsonValidationErrorFor('email');
    }

    public function test_it_refuses_a_password_that_is_not_confirmed(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Quentin',
            'email' => 'quentin@example.test',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'something-else',
        ])->assertUnprocessable()->assertJsonValidationErrorFor('password');
    }

    public function test_logging_in_returns_a_token(): void
    {
        User::factory()->create([
            'email' => 'quentin@example.test',
            'password' => Hash::make('correct-horse-battery-staple'),
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'quentin@example.test',
            'password' => 'correct-horse-battery-staple',
        ])
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer');
    }

    public function test_a_wrong_password_is_refused_without_saying_which_field_was_wrong(): void
    {
        User::factory()->create([
            'email' => 'quentin@example.test',
            'password' => Hash::make('correct-horse-battery-staple'),
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'quentin@example.test',
            'password' => 'wrong',
        ])->assertUnprocessable()->assertJsonValidationErrorFor('email');
    }

    public function test_an_unknown_email_is_refused_the_same_way(): void
    {
        $this->postJson('/api/v1/login', [
            'email' => 'nobody@example.test',
            'password' => 'whatever',
        ])->assertUnprocessable()->assertJsonValidationErrorFor('email');
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

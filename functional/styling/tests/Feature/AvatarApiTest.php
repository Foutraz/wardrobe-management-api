<?php

namespace Functional\Styling\Tests\Feature;

use Functional\Styling\Enums\StylingMediaCollection;
use Functional\Styling\Models\Avatar;
use Functional\Users\Enums\Ability;
use Functional\Users\Models\Permission;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AvatarApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_creating_an_avatar_attaches_it_to_the_signed_in_account(): void
    {
        $user = $this->member();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/avatars/mutate', [
            'mutate' => [[
                'operation' => 'create',
                'attributes' => ['name' => 'Moi en hiver'],
            ]],
        ])->assertSuccessful();

        $this->assertSame($user->id, Avatar::query()->firstOrFail()->user_id);
    }

    public function test_a_search_only_returns_the_avatars_of_the_signed_in_account(): void
    {
        $owner = $this->member();
        $stranger = $this->member();

        Avatar::factory()->create(['user_id' => $owner->id]);
        Avatar::factory()->count(2)->create(['user_id' => $stranger->id]);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/avatars/search', ['search' => []])->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_a_version_freezes_the_canonical_image_it_was_given(): void
    {
        $user = $this->member();
        $avatar = Avatar::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/avatars/{$avatar->id}/versions", [
            'image' => UploadedFile::fake()->image('moi.jpg', 900, 1600),
            'parameters' => ['height_cm' => 178, 'build' => 'athletic'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.has_canonical_image', true);

        $version = $avatar->fresh()->currentVersion();

        $this->assertNotNull($version);
        $this->assertSame(178, $version->parameters['height_cm']);
        $this->assertCount(1, $version->getMedia(StylingMediaCollection::CanonicalImage->value));
    }

    public function test_each_new_version_takes_the_next_number(): void
    {
        $user = $this->member();
        $avatar = Avatar::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        foreach ([1, 2, 3] as $expectedVersion) {
            $this->postJson("/api/v1/avatars/{$avatar->id}/versions", [
                'image' => UploadedFile::fake()->image('moi.jpg', 400, 700),
            ])
                ->assertCreated()
                ->assertJsonPath('data.version', $expectedVersion);
        }

        $this->assertSame(3, $avatar->fresh()->currentVersion()?->version);
    }

    public function test_it_refuses_a_version_without_an_image(): void
    {
        $user = $this->member();
        $avatar = Avatar::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/avatars/{$avatar->id}/versions", [
            'parameters' => ['height_cm' => 170],
        ])->assertUnprocessable();
    }

    public function test_it_refuses_an_out_of_range_height(): void
    {
        $user = $this->member();
        $avatar = Avatar::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/avatars/{$avatar->id}/versions", [
            'image' => UploadedFile::fake()->image('moi.jpg', 400, 700),
            'parameters' => ['height_cm' => 400],
        ])->assertUnprocessable();
    }

    public function test_an_account_cannot_add_a_version_to_an_avatar_it_does_not_own(): void
    {
        $owner = $this->member();
        $stranger = $this->member();
        $avatar = Avatar::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($stranger);

        $this->postJson("/api/v1/avatars/{$avatar->id}/versions", [
            'image' => UploadedFile::fake()->image('moi.jpg', 400, 700),
        ])->assertForbidden();
    }

    public function test_deleting_an_avatar_takes_its_versions_with_it(): void
    {
        $user = $this->member();
        $avatar = Avatar::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/avatars/{$avatar->id}/versions", [
            'image' => UploadedFile::fake()->image('moi.jpg', 400, 700),
        ])->assertCreated();

        $version = $avatar->fresh()->currentVersion();
        $avatar->delete();

        $this->assertModelMissing($version);
    }

    /**
     * Build an account holding the abilities an ordinary member is granted.
     */
    private function member(): User
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

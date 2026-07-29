<?php

namespace Functional\Catalog\Tests\Feature;

use Functional\Catalog\Models\Product;
use Functional\Catalog\Models\ProductVariant;
use Functional\Users\Enums\Ability;
use Functional\Users\Enums\UserRole;
use Functional\Users\Models\Permission;
use Functional\Users\Models\Role;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\MemberAccount;
use Tests\TestCase;

class BackOfficeApiTest extends TestCase
{
    use MemberAccount, RefreshDatabase;

    public function test_a_moderator_marks_a_contribution_as_reviewed(): void
    {
        $product = Product::factory()->contributed()->create();

        Sanctum::actingAs($this->moderator());

        $this->putJson("/api/v1/back-office/products/{$product->id}/verification")
            ->assertOk()
            ->assertJsonPath('data.is_verified', true)
            ->assertJsonPath('data.source_label', 'Contribution d’un utilisateur');

        $this->assertNotNull($product->fresh()->verified_at);
    }

    public function test_an_ordinary_member_cannot_moderate(): void
    {
        $product = Product::factory()->contributed()->create();

        Sanctum::actingAs($this->member());

        $this->putJson("/api/v1/back-office/products/{$product->id}/verification")
            ->assertForbidden();

        $this->assertNull($product->fresh()->verified_at);
    }

    public function test_moderation_is_closed_to_anonymous_callers(): void
    {
        $product = Product::factory()->contributed()->create();

        $this->putJson("/api/v1/back-office/products/{$product->id}/verification")
            ->assertUnauthorized();
    }

    public function test_a_moderator_folds_a_duplicate_into_the_entry_worth_keeping(): void
    {
        $survivor = Product::factory()->create(['name' => 'Chemise oxford']);
        $duplicate = Product::factory()->contributed()->create(['name' => 'chemise oxford']);

        ProductVariant::factory()->create(['product_id' => $survivor->id, 'size_label' => 'M', 'colour_name' => 'Blanc']);
        ProductVariant::factory()->create(['product_id' => $duplicate->id, 'size_label' => 'L', 'colour_name' => 'Blanc']);

        Sanctum::actingAs($this->moderator());

        $this->postJson("/api/v1/back-office/products/{$duplicate->id}/merge", ['into' => $survivor->id])
            ->assertOk()
            ->assertJsonPath('data.id', $survivor->id)
            ->assertJsonPath('data.variant_count', 2);

        $this->assertModelMissing($duplicate);
    }

    public function test_merging_a_product_into_itself_is_refused(): void
    {
        $product = Product::factory()->create();

        Sanctum::actingAs($this->moderator());

        $this->postJson("/api/v1/back-office/products/{$product->id}/merge", ['into' => $product->id])
            ->assertUnprocessable();
    }

    public function test_discarding_a_reviewed_entry_for_a_contribution_is_refused(): void
    {
        $reviewed = Product::factory()->create();
        $contribution = Product::factory()->contributed()->create();

        Sanctum::actingAs($this->moderator());

        $this->postJson("/api/v1/back-office/products/{$reviewed->id}/merge", ['into' => $contribution->id])
            ->assertUnprocessable();

        $this->assertModelExists($reviewed);
    }

    /**
     * Build an account holding the moderation abilities.
     */
    private function moderator(): User
    {
        foreach (Ability::cases() as $ability) {
            Permission::findOrCreate($ability->value);
        }

        $role = Role::findOrCreate(UserRole::Moderator->value);
        $role->syncPermissions(array_map(
            fn (Ability $ability): string => $ability->value,
            UserRole::Moderator->abilities(),
        ));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return User::factory()->create()->assignRole($role)->fresh();
    }
}

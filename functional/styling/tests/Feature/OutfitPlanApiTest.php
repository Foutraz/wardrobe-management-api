<?php

namespace Functional\Styling\Tests\Feature;

use Functional\Styling\Models\Outfit;
use Functional\Styling\Models\OutfitPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\MemberAccount;
use Tests\TestCase;

class OutfitPlanApiTest extends TestCase
{
    use MemberAccount, RefreshDatabase;

    public function test_it_rejects_an_unauthenticated_search(): void
    {
        $this->postJson('/api/v1/outfit-plans/search', ['search' => []])->assertUnauthorized();
    }

    public function test_scheduling_an_outfit_attaches_the_plan_to_the_signed_in_account(): void
    {
        $user = $this->member();
        $outfit = Outfit::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/outfit-plans/mutate', [
            'mutate' => [[
                'operation' => 'create',
                'attributes' => [
                    'outfit_id' => $outfit->id,
                    'scheduled_for' => '2026-08-14',
                ],
            ]],
        ])->assertSuccessful();

        $plan = OutfitPlan::query()->firstOrFail();

        $this->assertSame($user->id, $plan->user_id);
        $this->assertSame('2026-08-14', $plan->scheduled_for->toDateString());
    }

    public function test_a_search_only_returns_the_calendar_of_the_signed_in_account(): void
    {
        $owner = $this->member();
        $stranger = $this->member();

        $mine = OutfitPlan::factory()->count(2)->create(['user_id' => $owner->id]);
        OutfitPlan::factory()->count(3)->create(['user_id' => $stranger->id]);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/outfit-plans/search', ['search' => []])->assertOk();

        $returnedIds = array_column($response->json('data'), 'id');
        sort($returnedIds);

        $expectedIds = $mine->pluck('id')->all();
        sort($expectedIds);

        $this->assertSame($expectedIds, $returnedIds);
    }

    public function test_the_same_outfit_cannot_be_planned_twice_on_one_day(): void
    {
        $user = $this->member();
        $outfit = Outfit::factory()->create(['user_id' => $user->id]);

        OutfitPlan::factory()->create([
            'user_id' => $user->id,
            'outfit_id' => $outfit->id,
            'scheduled_for' => '2026-08-14',
        ]);

        $this->expectExceptionMessageMatches('/[Dd]uplicate|UNIQUE|unique/');

        OutfitPlan::factory()->create([
            'user_id' => $user->id,
            'outfit_id' => $outfit->id,
            'scheduled_for' => '2026-08-14',
        ]);
    }

    public function test_erasing_an_outfit_clears_the_days_it_was_planned_for(): void
    {
        $user = $this->member();
        $outfit = Outfit::factory()->create(['user_id' => $user->id]);
        $plan = OutfitPlan::factory()->create(['user_id' => $user->id, 'outfit_id' => $outfit->id]);

        $outfit->forceDelete();

        $this->assertModelMissing($plan);
    }
}

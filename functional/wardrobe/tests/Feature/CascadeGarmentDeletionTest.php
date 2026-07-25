<?php

namespace Functional\Wardrobe\Tests\Feature;

use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WearEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CascadeGarmentDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_a_garment_keeps_its_wear_history(): void
    {
        $garment = Garment::factory()->create();
        $wearEvent = WearEvent::factory()->create(['garment_id' => $garment->id]);

        $garment->delete();

        $this->assertSoftDeleted($garment);
        $this->assertModelExists($wearEvent);
    }

    public function test_force_deleting_a_garment_erases_its_wear_history(): void
    {
        $garment = Garment::factory()->create();
        $wearEvent = WearEvent::factory()->create(['garment_id' => $garment->id]);

        $garment->forceDelete();

        $this->assertModelMissing($garment);
        $this->assertModelMissing($wearEvent);
    }

    public function test_a_restored_garment_keeps_its_cost_per_wear(): void
    {
        $garment = Garment::factory()->create(['purchase_price_cents' => 10000]);
        WearEvent::factory()->count(4)->create(['garment_id' => $garment->id]);

        $garment->delete();
        $garment->restore();

        $this->assertSame(2500, $garment->costPerWear()->cents());
    }
}

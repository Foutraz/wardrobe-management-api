<?php

namespace Functional\Wardrobe\Tests\Unit;

use Functional\Wardrobe\Enums\GarmentAvailability;
use PHPUnit\Framework\TestCase;

class GarmentAvailabilityTest extends TestCase
{
    public function test_only_an_available_garment_can_be_worn(): void
    {
        $this->assertTrue(GarmentAvailability::Available->isAvailable());

        foreach (GarmentAvailability::cases() as $state) {
            if ($state !== GarmentAvailability::Available) {
                $this->assertFalse($state->isAvailable(), $state->value);
            }
        }
    }

    public function test_the_laundry_cycle_covers_dirty_washing_and_drying(): void
    {
        $this->assertTrue(GarmentAvailability::Dirty->isInLaundryCycle());
        $this->assertTrue(GarmentAvailability::InLaundry->isInLaundryCycle());
        $this->assertTrue(GarmentAvailability::Drying->isInLaundryCycle());

        $this->assertFalse(GarmentAvailability::Available->isInLaundryCycle());
        $this->assertFalse(GarmentAvailability::Lent->isInLaundryCycle());
        $this->assertFalse(GarmentAvailability::NeedsRepair->isInLaundryCycle());
    }

    public function test_a_garment_in_the_wash_or_lent_out_cannot_be_listed_for_resale(): void
    {
        $this->assertTrue(GarmentAvailability::Available->isSellable());
        $this->assertTrue(GarmentAvailability::Stored->isSellable());

        $this->assertFalse(GarmentAvailability::Dirty->isSellable());
        $this->assertFalse(GarmentAvailability::InLaundry->isSellable());
        $this->assertFalse(GarmentAvailability::Lent->isSellable());
        $this->assertFalse(GarmentAvailability::NeedsRepair->isSellable());
    }
}

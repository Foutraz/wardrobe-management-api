<?php

namespace Functional\Wardrobe\Tests\Unit;

use Functional\Wardrobe\Values\CostPerWear;
use PHPUnit\Framework\TestCase;

class CostPerWearTest extends TestCase
{
    public function test_it_stays_unknown_without_a_purchase_price(): void
    {
        $costPerWear = new CostPerWear(null, 5);

        $this->assertFalse($costPerWear->isKnown());
        $this->assertNull($costPerWear->cents());
    }

    public function test_it_stays_unknown_before_the_first_wear(): void
    {
        $costPerWear = new CostPerWear(10000, 0);

        $this->assertFalse($costPerWear->isKnown());
        $this->assertNull($costPerWear->cents());
    }

    public function test_it_divides_the_purchase_price_by_the_number_of_wears(): void
    {
        $this->assertSame(2500, (new CostPerWear(10000, 4))->cents());
    }

    public function test_it_rounds_to_the_nearest_cent(): void
    {
        $this->assertSame(3333, (new CostPerWear(10000, 3))->cents());
        $this->assertSame(1667, (new CostPerWear(10000, 6))->cents());
    }

    public function test_a_free_garment_costs_nothing_per_wear(): void
    {
        $costPerWear = new CostPerWear(0, 3);

        $this->assertTrue($costPerWear->isKnown());
        $this->assertSame(0, $costPerWear->cents());
    }
}

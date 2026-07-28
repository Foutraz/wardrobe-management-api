<?php

namespace Functional\Resale\Tests\Unit;

use Functional\Resale\Values\SuggestedPrice;
use Functional\Wardrobe\Enums\GarmentCondition;
use PHPUnit\Framework\TestCase;

class SuggestedPriceTest extends TestCase
{
    public function test_a_pristine_garment_asks_the_largest_share_of_its_purchase_price(): void
    {
        $this->assertSame(7000, SuggestedPrice::from(10000, 'EUR', GarmentCondition::NewWithTag)->cents);
    }

    public function test_a_worn_garment_asks_less(): void
    {
        $pristine = SuggestedPrice::from(10000, 'EUR', GarmentCondition::NewWithTag)->cents;
        $satisfactory = SuggestedPrice::from(10000, 'EUR', GarmentCondition::Satisfactory)->cents;

        $this->assertLessThan($pristine, $satisfactory);
    }

    public function test_the_price_never_falls_below_the_floor(): void
    {
        $this->assertSame(300, SuggestedPrice::from(100, 'EUR', GarmentCondition::Satisfactory)->cents);
    }

    public function test_an_unknown_purchase_price_falls_back_to_the_floor(): void
    {
        $this->assertSame(300, SuggestedPrice::from(null, 'EUR', GarmentCondition::VeryGood)->cents);
    }

    public function test_it_keeps_the_currency_the_garment_was_bought_in(): void
    {
        $this->assertSame('GBP', SuggestedPrice::from(5000, 'GBP', GarmentCondition::Good)->currency);
    }

    public function test_it_falls_back_to_euros_when_no_currency_is_known(): void
    {
        $this->assertSame('EUR', SuggestedPrice::from(5000, null, GarmentCondition::Good)->currency);
    }

    public function test_every_condition_yields_a_price(): void
    {
        foreach (GarmentCondition::cases() as $condition) {
            $this->assertGreaterThan(0, SuggestedPrice::from(20000, 'EUR', $condition)->cents, $condition->value);
        }
    }
}

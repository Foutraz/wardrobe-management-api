<?php

namespace Functional\Identification\Tests\Unit;

use Functional\Identification\Enums\IdentificationStatus;
use PHPUnit\Framework\TestCase;

class IdentificationStatusTest extends TestCase
{
    public function test_a_request_walks_from_pending_to_confirmed(): void
    {
        $this->assertTrue(IdentificationStatus::Pending->canTransitionTo(IdentificationStatus::Processing));
        $this->assertTrue(IdentificationStatus::Processing->canTransitionTo(IdentificationStatus::Succeeded));
        $this->assertTrue(IdentificationStatus::Succeeded->canTransitionTo(IdentificationStatus::Confirmed));
    }

    public function test_nothing_can_reach_confirmed_without_passing_through_succeeded(): void
    {
        foreach (IdentificationStatus::cases() as $status) {
            if ($status === IdentificationStatus::Succeeded) {
                continue;
            }

            $this->assertFalse(
                $status->canTransitionTo(IdentificationStatus::Confirmed),
                $status->value.' must not reach confirmed directly',
            );
        }
    }

    public function test_a_confirmed_request_is_final(): void
    {
        $this->assertSame([], IdentificationStatus::Confirmed->allowedTransitions());
    }

    public function test_a_failed_request_can_be_tried_again(): void
    {
        $this->assertTrue(IdentificationStatus::Failed->canTransitionTo(IdentificationStatus::Pending));
        $this->assertFalse(IdentificationStatus::Failed->canTransitionTo(IdentificationStatus::Succeeded));
    }

    public function test_only_a_succeeded_request_awaits_confirmation(): void
    {
        $this->assertTrue(IdentificationStatus::Succeeded->awaitsConfirmation());

        foreach ([IdentificationStatus::Pending, IdentificationStatus::Processing, IdentificationStatus::Failed, IdentificationStatus::Confirmed] as $status) {
            $this->assertFalse($status->awaitsConfirmation(), $status->value);
        }
    }

    public function test_attributes_are_readable_once_the_work_is_done(): void
    {
        $this->assertTrue(IdentificationStatus::Succeeded->hasAttributes());
        $this->assertTrue(IdentificationStatus::Confirmed->hasAttributes());
        $this->assertFalse(IdentificationStatus::Pending->hasAttributes());
        $this->assertFalse(IdentificationStatus::Failed->hasAttributes());
    }
}

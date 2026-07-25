<?php

namespace Functional\Styling\Tests\Unit;

use Functional\Styling\Enums\RenderStatus;
use PHPUnit\Framework\TestCase;

class RenderStatusTest extends TestCase
{
    public function test_a_render_walks_from_pending_through_processing_to_succeeded(): void
    {
        $this->assertTrue(RenderStatus::Pending->canTransitionTo(RenderStatus::Processing));
        $this->assertTrue(RenderStatus::Processing->canTransitionTo(RenderStatus::Succeeded));
    }

    public function test_a_succeeded_render_is_final(): void
    {
        $this->assertSame([], RenderStatus::Succeeded->allowedTransitions());

        foreach (RenderStatus::cases() as $status) {
            $this->assertFalse(RenderStatus::Succeeded->canTransitionTo($status), $status->value);
        }
    }

    public function test_a_failed_render_can_be_queued_again_but_not_declared_successful(): void
    {
        $this->assertTrue(RenderStatus::Failed->canTransitionTo(RenderStatus::Pending));
        $this->assertFalse(RenderStatus::Failed->canTransitionTo(RenderStatus::Succeeded));
        $this->assertFalse(RenderStatus::Failed->canTransitionTo(RenderStatus::Processing));
    }

    public function test_pending_cannot_jump_straight_to_succeeded(): void
    {
        $this->assertFalse(RenderStatus::Pending->canTransitionTo(RenderStatus::Succeeded));
    }

    public function test_only_a_succeeded_render_carries_an_image(): void
    {
        $this->assertTrue(RenderStatus::Succeeded->hasImage());
        $this->assertFalse(RenderStatus::Pending->hasImage());
        $this->assertFalse(RenderStatus::Processing->hasImage());
        $this->assertFalse(RenderStatus::Failed->hasImage());
    }
}

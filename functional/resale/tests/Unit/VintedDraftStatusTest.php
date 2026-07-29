<?php

namespace Functional\Resale\Tests\Unit;

use Functional\Resale\Enums\VintedDraftStatus;
use PHPUnit\Framework\TestCase;

class VintedDraftStatusTest extends TestCase
{
    public function test_a_draft_walks_to_ready_then_to_the_seller(): void
    {
        $this->assertTrue(VintedDraftStatus::Draft->canTransitionTo(VintedDraftStatus::Ready));
        $this->assertTrue(VintedDraftStatus::Ready->canTransitionTo(VintedDraftStatus::HandedOff));
        $this->assertTrue(VintedDraftStatus::HandedOff->canTransitionTo(VintedDraftStatus::PublishedByUser));
    }

    public function test_a_handed_off_draft_never_returns_to_editing(): void
    {
        $this->assertFalse(VintedDraftStatus::HandedOff->canTransitionTo(VintedDraftStatus::Draft));
        $this->assertFalse(VintedDraftStatus::HandedOff->canTransitionTo(VintedDraftStatus::Ready));
    }

    public function test_a_ready_draft_can_be_reopened_for_editing(): void
    {
        $this->assertTrue(VintedDraftStatus::Ready->canTransitionTo(VintedDraftStatus::Draft));
    }

    public function test_a_draft_cannot_skip_straight_to_published(): void
    {
        $this->assertFalse(VintedDraftStatus::Draft->canTransitionTo(VintedDraftStatus::PublishedByUser));
        $this->assertFalse(VintedDraftStatus::Ready->canTransitionTo(VintedDraftStatus::PublishedByUser));
    }

    public function test_the_final_states_go_nowhere(): void
    {
        $this->assertSame([], VintedDraftStatus::PublishedByUser->allowedTransitions());
        $this->assertSame([], VintedDraftStatus::Abandoned->allowedTransitions());
        $this->assertTrue(VintedDraftStatus::PublishedByUser->isFinal());
        $this->assertTrue(VintedDraftStatus::Abandoned->isFinal());
    }

    public function test_any_live_draft_can_be_abandoned(): void
    {
        foreach ([VintedDraftStatus::Draft, VintedDraftStatus::Ready, VintedDraftStatus::HandedOff] as $status) {
            $this->assertTrue($status->canTransitionTo(VintedDraftStatus::Abandoned), $status->value);
        }
    }

    public function test_only_a_draft_or_a_ready_listing_is_still_ours_to_edit(): void
    {
        $this->assertTrue(VintedDraftStatus::Draft->isEditable());
        $this->assertTrue(VintedDraftStatus::Ready->isEditable());
        $this->assertFalse(VintedDraftStatus::HandedOff->isEditable());
        $this->assertFalse(VintedDraftStatus::PublishedByUser->isEditable());
    }
}

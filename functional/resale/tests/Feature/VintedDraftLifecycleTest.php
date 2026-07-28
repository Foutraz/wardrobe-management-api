<?php

namespace Functional\Resale\Tests\Feature;

use Functional\Resale\Enums\VintedDraftStatus;
use Functional\Resale\Exceptions\IllegalDraftTransition;
use Functional\Resale\Models\VintedListingDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VintedDraftLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_transition_actually_reaches_the_database(): void
    {
        $draft = VintedListingDraft::factory()->create();

        $draft->transitionTo(VintedDraftStatus::Ready);

        $this->assertSame(VintedDraftStatus::Ready, $draft->fresh()->status);
    }

    public function test_handing_off_stamps_the_moment_it_happened(): void
    {
        $draft = VintedListingDraft::factory()->ready()->create();

        $this->assertNull($draft->handed_off_at);

        $draft->transitionTo(VintedDraftStatus::HandedOff);

        $this->assertNotNull($draft->fresh()->handed_off_at);
    }

    public function test_the_handoff_stamp_survives_a_later_transition(): void
    {
        $draft = VintedListingDraft::factory()->ready()->create();

        $draft->transitionTo(VintedDraftStatus::HandedOff);
        $stampedAt = $draft->fresh()->handed_off_at;

        $draft->transitionTo(VintedDraftStatus::PublishedByUser);

        $this->assertEquals($stampedAt, $draft->fresh()->handed_off_at);
    }

    public function test_an_illegal_transition_throws(): void
    {
        $draft = VintedListingDraft::factory()->handedOff()->create();

        $this->expectException(IllegalDraftTransition::class);

        $draft->transitionTo(VintedDraftStatus::Draft);
    }

    public function test_the_guard_runs_before_anything_is_written(): void
    {
        $draft = VintedListingDraft::factory()->handedOff()->create();

        $this->assertFalse($draft->status->canTransitionTo(VintedDraftStatus::Draft));
        $this->assertSame(VintedDraftStatus::HandedOff, $draft->fresh()->status);
    }

    public function test_a_published_draft_is_the_end_of_the_road(): void
    {
        $draft = VintedListingDraft::factory()->handedOff()->create();
        $draft->transitionTo(VintedDraftStatus::PublishedByUser);

        $this->expectException(IllegalDraftTransition::class);

        $draft->transitionTo(VintedDraftStatus::Abandoned);
    }
}

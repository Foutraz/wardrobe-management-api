<?php

namespace Functional\Resale\Models;

use Functional\Resale\Database\Factories\VintedListingDraftFactory;
use Functional\Resale\Enums\VintedDraftStatus;
use Functional\Resale\Exceptions\IllegalDraftTransition;
use Functional\Resale\Policies\VintedListingDraftPolicy;
use Functional\Users\Models\User;
use Functional\Wardrobe\Models\Garment;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Lomkit\Access\Controls\HasControl;

/**
 * @property string $id
 * @property string $user_id
 * @property string $garment_id
 * @property string $title
 * @property string $description
 * @property string|null $brand_label
 * @property string|null $size_label
 * @property string|null $colour_label
 * @property string $condition_label
 * @property int $price_cents
 * @property string $currency
 * @property VintedDraftStatus $status
 * @property Carbon|null $handed_off_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'title',
    'description',
    'brand_label',
    'size_label',
    'colour_label',
    'condition_label',
    'price_cents',
    'currency',
])]
#[UseFactory(VintedListingDraftFactory::class)]
#[UsePolicy(VintedListingDraftPolicy::class)]
class VintedListingDraft extends Model
{
    use HasControl, HasUlids;

    /** @use HasFactory<VintedListingDraftFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Garment, $this>
     */
    public function garment(): BelongsTo
    {
        return $this->belongsTo(Garment::class);
    }

    /**
     * Move the draft to a new state, refusing any transition the lifecycle forbids.
     *
     * @throws IllegalDraftTransition
     */
    public function transitionTo(VintedDraftStatus $status): void
    {
        if (! $this->status->canTransitionTo($status)) {
            throw IllegalDraftTransition::between($this->status, $status);
        }

        $this->forceFill([
            'status' => $status,
            'handed_off_at' => $status === VintedDraftStatus::HandedOff ? now() : $this->handed_off_at,
        ])->save();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VintedDraftStatus::class,
            'price_cents' => 'integer',
            'handed_off_at' => 'datetime',
        ];
    }
}

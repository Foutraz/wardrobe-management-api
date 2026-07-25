<?php

namespace Functional\Styling\Models;

use Functional\Styling\Database\Factories\OutfitPreviewFactory;
use Functional\Styling\Enums\PreviewMode;
use Functional\Styling\Enums\RenderStatus;
use Functional\Styling\Enums\StylingMediaCollection;
use Functional\Styling\Exceptions\IllegalRenderTransition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string $outfit_id
 * @property string|null $avatar_version_id
 * @property PreviewMode $mode
 * @property RenderStatus $status
 * @property string $cache_key
 * @property string|null $provider
 * @property int $cost_cents
 * @property string|null $failure_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'outfit_id',
    'avatar_version_id',
    'mode',
    'status',
    'cache_key',
    'provider',
    'cost_cents',
    'failure_reason',
])]
#[UseFactory(OutfitPreviewFactory::class)]
class OutfitPreview extends Model implements HasMedia
{
    /** @use HasFactory<OutfitPreviewFactory> */
    use HasFactory;

    use HasUlids, InteractsWithMedia;

    /**
     * @return BelongsTo<Outfit, $this>
     */
    public function outfit(): BelongsTo
    {
        return $this->belongsTo(Outfit::class);
    }

    /**
     * @return BelongsTo<AvatarVersion, $this>
     */
    public function avatarVersion(): BelongsTo
    {
        return $this->belongsTo(AvatarVersion::class);
    }

    /**
     * Hold the single rendered image this preview produced.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(StylingMediaCollection::Render->value)
            ->acceptsMimeTypes(StylingMediaCollection::Render->acceptedMimeTypes())
            ->singleFile();
    }

    /**
     * Move the render to a new state, refusing any transition the lifecycle forbids.
     *
     * @throws IllegalRenderTransition
     */
    public function transitionTo(RenderStatus $status, ?string $failureReason = null): void
    {
        if (! $this->status->canTransitionTo($status)) {
            throw IllegalRenderTransition::between($this->status, $status);
        }

        $this->update([
            'status' => $status,
            'failure_reason' => $failureReason,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => PreviewMode::class,
            'status' => RenderStatus::class,
            'cost_cents' => 'integer',
        ];
    }
}

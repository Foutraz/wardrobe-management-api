<?php

namespace Functional\Styling\Models;

use Functional\Styling\Database\Factories\AvatarVersionFactory;
use Functional\Styling\Enums\StylingMediaCollection;
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
 * @property string $avatar_id
 * @property int $version
 * @property array<string, mixed> $parameters
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['avatar_id', 'version', 'parameters'])]
#[UseFactory(AvatarVersionFactory::class)]
class AvatarVersion extends Model implements HasMedia
{
    /** @use HasFactory<AvatarVersionFactory> */
    use HasFactory;

    use HasUlids, InteractsWithMedia;

    /**
     * @return BelongsTo<Avatar, $this>
     */
    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Avatar::class);
    }

    /**
     * Hold the one photorealistic body image every try-on renders against.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(StylingMediaCollection::CanonicalImage->value)
            ->acceptsMimeTypes(StylingMediaCollection::CanonicalImage->acceptedMimeTypes())
            ->singleFile();
    }

    /**
     * Determine whether this version can actually be rendered against.
     */
    public function hasCanonicalImage(): bool
    {
        return $this->getMedia(StylingMediaCollection::CanonicalImage->value)->isNotEmpty();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'version' => 'integer',
        ];
    }
}

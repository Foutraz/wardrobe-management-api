<?php

namespace Functional\Identification\Models;

use Functional\Catalog\Models\ProductVariant;
use Functional\Identification\Database\Factories\IdentificationRequestFactory;
use Functional\Identification\Enums\IdentificationKind;
use Functional\Identification\Enums\IdentificationStatus;
use Functional\Identification\Exceptions\IllegalIdentificationTransition;
use Functional\Users\Models\User;
use Functional\Wardrobe\Models\Garment;
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
 * @property string $user_id
 * @property IdentificationKind $kind
 * @property IdentificationStatus $status
 * @property string|null $barcode
 * @property string|null $resolved_product_variant_id
 * @property string|null $created_garment_id
 * @property array<string, mixed> $extracted_attributes
 * @property int|null $confidence
 * @property string|null $provider
 * @property string|null $failure_reason
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['kind', 'barcode'])]
#[UseFactory(IdentificationRequestFactory::class)]
class IdentificationRequest extends Model implements HasMedia
{
    /** @use HasFactory<IdentificationRequestFactory> */
    use HasFactory;

    use HasUlids, InteractsWithMedia;

    public const SUBJECT_COLLECTION = 'subject';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function resolvedProductVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'resolved_product_variant_id');
    }

    /**
     * @return BelongsTo<Garment, $this>
     */
    public function createdGarment(): BelongsTo
    {
        return $this->belongsTo(Garment::class, 'created_garment_id');
    }

    /**
     * Hold the photo or label shot the identification works from.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::SUBJECT_COLLECTION)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->singleFile();
    }

    /**
     * Move the request to a new state, refusing any transition the lifecycle forbids.
     *
     * @param  array<string, mixed>  $changes
     *
     * @throws IllegalIdentificationTransition
     */
    public function transitionTo(IdentificationStatus $status, array $changes = []): void
    {
        if (! $this->status->canTransitionTo($status)) {
            throw IllegalIdentificationTransition::between($this->status, $status);
        }

        $this->forceFill([...$changes, 'status' => $status])->save();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => IdentificationKind::class,
            'status' => IdentificationStatus::class,
            'extracted_attributes' => 'array',
            'confidence' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }
}

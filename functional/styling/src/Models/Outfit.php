<?php

namespace Functional\Styling\Models;

use Functional\Styling\Database\Factories\OutfitFactory;
use Functional\Styling\Enums\Season;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property string|null $occasion
 * @property Season|null $season
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'occasion', 'season', 'notes'])]
#[UseFactory(OutfitFactory::class)]
class Outfit extends Model
{
    /** @use HasFactory<OutfitFactory> */
    use HasFactory;

    use HasUlids, Prunable, SoftDeletes;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OutfitItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OutfitItem::class)->orderBy('sort');
    }

    /**
     * @return HasMany<OutfitPreview, $this>
     */
    public function previews(): HasMany
    {
        return $this->hasMany(OutfitPreview::class);
    }

    /**
     * @return HasMany<OutfitPlan, $this>
     */
    public function plans(): HasMany
    {
        return $this->hasMany(OutfitPlan::class);
    }

    /**
     * Erase soft-deleted outfits for good once their thirty day grace period has elapsed.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subDays(30));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'season' => Season::class,
        ];
    }
}

<?php

namespace Functional\Styling\Models;

use Functional\Styling\Database\Factories\OutfitItemFactory;
use Functional\Styling\Enums\OutfitSlot;
use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WishlistItem;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $outfit_id
 * @property string|null $garment_id
 * @property string|null $wishlist_item_id
 * @property OutfitSlot $slot
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['garment_id', 'wishlist_item_id', 'slot', 'sort'])]
#[UseFactory(OutfitItemFactory::class)]
class OutfitItem extends Model
{
    /** @use HasFactory<OutfitItemFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * @return BelongsTo<Outfit, $this>
     */
    public function outfit(): BelongsTo
    {
        return $this->belongsTo(Outfit::class);
    }

    /**
     * @return BelongsTo<Garment, $this>
     */
    public function garment(): BelongsTo
    {
        return $this->belongsTo(Garment::class);
    }

    /**
     * @return BelongsTo<WishlistItem, $this>
     */
    public function wishlistItem(): BelongsTo
    {
        return $this->belongsTo(WishlistItem::class);
    }

    /**
     * Determine whether the item stands for something the owner already has.
     */
    public function isOwned(): bool
    {
        return $this->garment_id !== null;
    }

    /**
     * Get the identifier that takes part in a preview cache key.
     */
    public function cacheIdentifier(): string
    {
        return $this->garment_id ?? (string) $this->wishlist_item_id;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slot' => OutfitSlot::class,
            'sort' => 'integer',
        ];
    }
}

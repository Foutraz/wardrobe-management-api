<?php

namespace Functional\Wardrobe\Models;

use Functional\Catalog\Models\Brand;
use Functional\Catalog\Models\Category;
use Functional\Catalog\Models\ProductVariant;
use Functional\Users\Models\User;
use Functional\Wardrobe\Database\Factories\GarmentFactory;
use Functional\Wardrobe\Enums\GarmentAvailability;
use Functional\Wardrobe\Enums\GarmentCondition;
use Functional\Wardrobe\Policies\GarmentPolicy;
use Functional\Wardrobe\Values\CostPerWear;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Lomkit\Access\Controls\HasControl;

/**
 * @property string $id
 * @property string $user_id
 * @property string|null $product_variant_id
 * @property string $category_id
 * @property string|null $brand_id
 * @property string $name
 * @property string|null $size_label
 * @property string|null $colour_name
 * @property string|null $colour_hex
 * @property string|null $material_composition
 * @property GarmentCondition $condition
 * @property GarmentAvailability $availability_status
 * @property int|null $purchase_price_cents
 * @property string|null $purchase_currency
 * @property Carbon|null $purchased_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read int|null $wear_events_count
 */
#[Fillable([
    'product_variant_id',
    'category_id',
    'brand_id',
    'name',
    'size_label',
    'colour_name',
    'colour_hex',
    'material_composition',
    'condition',
    'availability_status',
    'purchase_price_cents',
    'purchase_currency',
    'purchased_at',
    'notes',
])]
#[UseFactory(GarmentFactory::class)]
#[UsePolicy(GarmentPolicy::class)]
class Garment extends Model
{
    use HasControl, HasUlids, Prunable, SoftDeletes;

    /** @use HasFactory<GarmentFactory> */
    use HasFactory;

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
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<WearEvent, $this>
     */
    public function wearEvents(): HasMany
    {
        return $this->hasMany(WearEvent::class);
    }

    /**
     * Compute what a single wear of this garment has cost so far.
     */
    public function costPerWear(): CostPerWear
    {
        return new CostPerWear(
            $this->purchase_price_cents,
            $this->wear_events_count ?? $this->wearEvents()->count(),
        );
    }

    /**
     * Erase soft-deleted garments for good once their thirty day grace period has elapsed.
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
            'condition' => GarmentCondition::class,
            'availability_status' => GarmentAvailability::class,
            'purchased_at' => 'date',
        ];
    }
}

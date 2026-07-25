<?php

namespace Functional\Wardrobe\Models;

use Functional\Catalog\Models\Brand;
use Functional\Catalog\Models\Category;
use Functional\Catalog\Models\ProductVariant;
use Functional\Users\Models\User;
use Functional\Wardrobe\Database\Factories\GarmentFactory;
use Functional\Wardrobe\Enums\GarmentAvailability;
use Functional\Wardrobe\Enums\GarmentCondition;
use Functional\Wardrobe\Values\CostPerWear;
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
class Garment extends Model
{
    use HasFactory, HasUlids, Prunable, SoftDeletes;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

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

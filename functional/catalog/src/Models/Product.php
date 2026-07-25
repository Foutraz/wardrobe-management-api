<?php

namespace Functional\Catalog\Models;

use Functional\Catalog\Database\Factories\ProductFactory;
use Functional\Catalog\Enums\ProductSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'brand_id',
    'category_id',
    'name',
    'style_reference',
    'material_composition',
    'retail_price_cents',
    'currency',
    'source',
])]
#[UseFactory(ProductFactory::class)]
class Product extends Model
{
    use HasFactory, HasUlids;

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Determine whether a moderator has reviewed this catalogue entry.
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => ProductSource::class,
            'verified_at' => 'datetime',
        ];
    }
}

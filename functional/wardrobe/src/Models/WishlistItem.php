<?php

namespace Functional\Wardrobe\Models;

use Functional\Catalog\Models\ProductVariant;
use Functional\Users\Models\User;
use Functional\Wardrobe\Database\Factories\WishlistItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_variant_id',
    'name',
    'brand_label',
    'size_label',
    'colour_name',
    'colour_hex',
    'external_url',
    'price_cents',
    'currency',
])]
#[UseFactory(WishlistItemFactory::class)]
class WishlistItem extends Model
{
    use HasFactory, HasUlids;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}

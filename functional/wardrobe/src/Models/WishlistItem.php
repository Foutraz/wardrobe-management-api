<?php

namespace Functional\Wardrobe\Models;

use Functional\Catalog\Models\ProductVariant;
use Functional\Users\Models\User;
use Functional\Wardrobe\Database\Factories\WishlistItemFactory;
use Functional\Wardrobe\Policies\WishlistItemPolicy;
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
 * @property string|null $product_variant_id
 * @property string $name
 * @property string|null $brand_label
 * @property string|null $size_label
 * @property string|null $colour_name
 * @property string|null $colour_hex
 * @property string|null $external_url
 * @property int|null $price_cents
 * @property string|null $currency
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
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
#[UsePolicy(WishlistItemPolicy::class)]
class WishlistItem extends Model
{
    use HasControl, HasUlids;

    /** @use HasFactory<WishlistItemFactory> */
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
}

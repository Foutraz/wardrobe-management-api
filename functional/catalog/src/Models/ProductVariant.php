<?php

namespace Functional\Catalog\Models;

use Functional\Catalog\Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'size_label', 'colour_name', 'colour_hex', 'ean', 'sku'])]
#[UseFactory(ProductVariantFactory::class)]
class ProductVariant extends Model
{
    use HasFactory, HasUlids;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace Functional\Catalog\Models;

use Functional\Catalog\Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
#[UseFactory(BrandFactory::class)]
class Brand extends Model
{
    use HasFactory, HasUlids;

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}

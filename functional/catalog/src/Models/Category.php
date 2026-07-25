<?php

namespace Functional\Catalog\Models;

use Functional\Catalog\Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['parent_id', 'slug', 'sort'])]
#[UseFactory(CategoryFactory::class)]
class Category extends Model
{
    use HasFactory, HasUlids;

    protected $appends = ['label'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Resolve the human readable name from the translation files keyed by slug.
     */
    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => __('catalog::category.'.$this->slug));
    }
}

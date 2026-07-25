<?php

namespace Functional\Catalog\Database\Factories;

use Functional\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'parent_id' => null,
            'slug' => Str::slug(faker()->words(2).' '.Str::substr(faker()->ulid(), -5)),
            'sort' => faker()->number(0, 100),
        ];
    }

    /**
     * Nest the category under a freshly created parent.
     */
    public function nested(): static
    {
        return $this->state(fn (array $attributes): array => ['parent_id' => self::new()]);
    }
}

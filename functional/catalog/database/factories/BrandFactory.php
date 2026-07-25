<?php

namespace Functional\Catalog\Database\Factories;

use Functional\Catalog\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $name = Str::title(faker()->words(2)).' '.Str::substr(faker()->ulid(), -5);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}

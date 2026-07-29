<?php

namespace Functional\Catalog\Database\Seeders;

use Functional\Catalog\Models\Category;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private const TAXONOMY = [
        'tops' => ['t-shirt', 'shirt', 'polo', 'blouse', 'sweater', 'hoodie'],
        'bottoms' => ['jeans', 'trousers', 'shorts', 'skirt', 'leggings'],
        'dresses' => ['dress', 'jumpsuit'],
        'outerwear' => ['coat', 'parka', 'jacket', 'blazer'],
        'shoes' => ['sneakers', 'boots', 'sandals', 'heels'],
        'accessories' => ['bag', 'belt', 'hat', 'scarf', 'jewellery'],
        'underwear' => ['bra', 'briefs', 'socks', 'tights'],
        'sportswear' => ['sports-top', 'sports-bottom', 'swimwear'],
    ];

    public function run(): void
    {
        $parentSort = 0;

        foreach (self::TAXONOMY as $parentSlug => $childSlugs) {
            $parent = Category::firstOrCreate(
                ['slug' => $parentSlug],
                ['parent_id' => null, 'sort' => $parentSort += 10],
            );

            $childSort = 0;

            foreach ($childSlugs as $childSlug) {
                Category::firstOrCreate(
                    ['slug' => $childSlug],
                    ['parent_id' => $parent->id, 'sort' => $childSort += 10],
                );
            }
        }
    }
}

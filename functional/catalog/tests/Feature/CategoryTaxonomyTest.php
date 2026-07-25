<?php

namespace Functional\Catalog\Tests\Feature;

use Functional\Catalog\Database\Seeders\CatalogSeeder;
use Functional\Catalog\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_builds_the_whole_taxonomy(): void
    {
        (new CatalogSeeder)->run();

        $this->assertSame(8, Category::query()->whereNull('parent_id')->count());
        $this->assertSame(33, Category::query()->whereNotNull('parent_id')->count());
    }

    public function test_the_seeder_can_run_twice_without_duplicating_anything(): void
    {
        (new CatalogSeeder)->run();
        (new CatalogSeeder)->run();

        $this->assertSame(41, Category::query()->count());
    }

    public function test_every_seeded_category_resolves_a_label_in_both_locales(): void
    {
        (new CatalogSeeder)->run();

        foreach (['fr', 'en'] as $locale) {
            app()->setLocale($locale);

            Category::query()->cursor()->each(function (Category $category) use ($locale): void {
                $this->assertNotSame(
                    'catalog::category.'.$category->slug,
                    $category->label,
                    "Missing {$locale} translation for {$category->slug}",
                );
            });
        }
    }

    public function test_a_label_follows_the_active_locale(): void
    {
        $category = Category::factory()->create(['slug' => 'tops']);

        app()->setLocale('fr');
        $this->assertSame('Hauts', $category->label);

        app()->setLocale('en');
        $this->assertSame('Tops', $category->label);
    }

    public function test_children_hang_off_their_parent(): void
    {
        (new CatalogSeeder)->run();

        $tops = Category::query()->where('slug', 'tops')->firstOrFail();

        $this->assertCount(6, $tops->children);
        $this->assertSame('tops', $tops->children->first()->parent->slug);
    }
}

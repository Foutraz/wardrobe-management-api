<?php

namespace Functional\Wardrobe\Database\Seeders;

use Functional\Catalog\Models\Category;
use Functional\Users\Models\User;
use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WearEvent;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class WardrobeSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::query()->whereNotNull('parent_id')->pluck('id');

        if ($categories->isEmpty()) {
            return;
        }

        User::query()->cursor()->each(function (User $user) use ($categories): void {
            if (Garment::query()->where('user_id', $user->id)->exists()) {
                return;
            }

            Garment::factory()
                ->count(12)
                ->sequence(fn (Sequence $sequence): array => [
                    'category_id' => $categories->random(),
                ])
                ->create(['user_id' => $user->id])
                ->each(fn (Garment $garment) => WearEvent::factory()
                    ->count(faker()->number(0, 8))
                    ->create(['garment_id' => $garment->id]));
        });
    }
}

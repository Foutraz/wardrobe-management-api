<?php

namespace Functional\Styling\Tests\Feature;

use Functional\Styling\Enums\OutfitSlot;
use Functional\Styling\Models\Outfit;
use Functional\Wardrobe\Models\Garment;
use Functional\Wardrobe\Models\WishlistItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class OutfitItemCheckConstraintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
            $this->markTestSkipped('This driver cannot carry the check constraint, so the listener stands alone here.');
        }
    }

    public function test_the_database_itself_refuses_a_row_referencing_nothing(): void
    {
        $outfit = Outfit::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('outfit_items')->insert($this->row($outfit, null, null));
    }

    public function test_the_database_itself_refuses_a_row_referencing_both(): void
    {
        $outfit = Outfit::factory()->create();
        $garment = Garment::factory()->create();
        $wishlistItemId = WishlistItem::factory()->create()->id;

        $this->expectException(QueryException::class);

        DB::table('outfit_items')->insert($this->row($outfit, $garment->id, $wishlistItemId));
    }

    public function test_the_database_accepts_a_row_referencing_exactly_one_thing(): void
    {
        $outfit = Outfit::factory()->create();
        $garment = Garment::factory()->create();

        DB::table('outfit_items')->insert($this->row($outfit, $garment->id, null));

        $this->assertDatabaseHas('outfit_items', [
            'outfit_id' => $outfit->id,
            'garment_id' => $garment->id,
        ]);
    }

    /**
     * Build a raw row so the insert bypasses the model listener and reaches the constraint.
     *
     * @return array<string, mixed>
     */
    private function row(Outfit $outfit, ?string $garmentId, ?string $wishlistItemId): array
    {
        return [
            'id' => (string) Str::ulid(),
            'outfit_id' => $outfit->id,
            'garment_id' => $garmentId,
            'wishlist_item_id' => $wishlistItemId,
            'slot' => OutfitSlot::Top->value,
            'sort' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

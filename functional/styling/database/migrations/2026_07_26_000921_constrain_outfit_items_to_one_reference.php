<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONSTRAINT = 'outfit_items_exactly_one_reference';

    public function up(): void
    {
        if (! $this->driverSupportsCheckConstraints()) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE outfit_items ADD CONSTRAINT %s CHECK ((garment_id IS NULL) <> (wishlist_item_id IS NULL))',
            self::CONSTRAINT,
        ));
    }

    public function down(): void
    {
        if (! $this->driverSupportsCheckConstraints()) {
            return;
        }

        DB::statement(sprintf('ALTER TABLE outfit_items DROP CONSTRAINT %s', self::CONSTRAINT));
    }

    /**
     * SQLite cannot add a check constraint to an existing table, so there the listener stands alone.
     */
    private function driverSupportsCheckConstraints(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb', 'pgsql'], true);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outfit_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('outfit_id')->constrained();
            $table->foreignUlid('garment_id')->nullable()->constrained();
            $table->foreignUlid('wishlist_item_id')->nullable()->constrained();
            $table->string('slot', 32);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['outfit_id', 'sort']);
            $table->unique(['outfit_id', 'garment_id']);
            $table->unique(['outfit_id', 'wishlist_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outfit_items');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained();
            $table->foreignUlid('product_variant_id')->nullable()->constrained();
            $table->string('name');
            $table->string('brand_label')->nullable();
            $table->string('size_label')->nullable();
            $table->string('colour_name')->nullable();
            $table->char('colour_hex', 7)->nullable();
            $table->string('external_url')->nullable();
            $table->unsignedInteger('price_cents')->nullable();
            $table->char('currency', 3)->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};

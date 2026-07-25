<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained();
            $table->string('size_label');
            $table->string('colour_name')->nullable();
            $table->char('colour_hex', 7)->nullable();
            $table->string('ean', 14)->nullable()->unique();
            $table->string('sku')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'size_label', 'colour_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};

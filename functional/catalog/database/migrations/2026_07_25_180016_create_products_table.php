<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('brand_id')->nullable()->constrained();
            $table->foreignUlid('category_id')->constrained();
            $table->string('name');
            $table->string('style_reference')->nullable();
            $table->string('material_composition')->nullable();
            $table->unsignedInteger('retail_price_cents')->nullable();
            $table->char('currency', 3)->nullable();
            $table->string('source', 32);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'name']);
            $table->index('verified_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

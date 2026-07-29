<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained();
            $table->foreignUlid('product_variant_id')->nullable()->constrained();
            $table->foreignUlid('category_id')->constrained();
            $table->foreignUlid('brand_id')->nullable()->constrained();
            $table->string('name');
            $table->string('size_label')->nullable();
            $table->string('colour_name')->nullable();
            $table->char('colour_hex', 7)->nullable();
            $table->string('material_composition')->nullable();
            $table->string('condition', 32);
            $table->string('availability_status', 32);
            $table->unsignedInteger('purchase_price_cents')->nullable();
            $table->char('purchase_currency', 3)->nullable();
            $table->date('purchased_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'availability_status']);
            $table->index(['user_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garments');
    }
};

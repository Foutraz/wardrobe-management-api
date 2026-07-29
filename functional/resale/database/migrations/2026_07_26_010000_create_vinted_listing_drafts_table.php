<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vinted_listing_drafts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained();
            $table->foreignUlid('garment_id')->constrained();
            $table->string('title');
            $table->text('description');
            $table->string('brand_label')->nullable();
            $table->string('size_label')->nullable();
            $table->string('colour_label')->nullable();
            $table->string('condition_label');
            $table->unsignedInteger('price_cents');
            $table->char('currency', 3);
            $table->string('status', 32);
            $table->timestamp('handed_off_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['garment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vinted_listing_drafts');
    }
};

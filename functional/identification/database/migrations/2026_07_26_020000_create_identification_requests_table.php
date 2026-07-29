<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identification_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained();
            $table->string('kind', 32);
            $table->string('status', 32);
            $table->string('barcode', 14)->nullable();
            $table->foreignUlid('resolved_product_variant_id')->nullable()->constrained('product_variants');
            $table->foreignUlid('created_garment_id')->nullable()->constrained('garments');
            $table->json('extracted_attributes');
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->string('provider', 64)->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('barcode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identification_requests');
    }
};

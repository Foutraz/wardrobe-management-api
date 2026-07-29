<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wear_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('garment_id')->constrained();
            $table->date('worn_on');
            $table->timestamps();

            $table->index(['garment_id', 'worn_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wear_events');
    }
};

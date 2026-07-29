<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avatar_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('avatar_id')->constrained();
            $table->unsignedInteger('version');
            $table->json('parameters');
            $table->timestamps();

            $table->unique(['avatar_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avatar_versions');
    }
};

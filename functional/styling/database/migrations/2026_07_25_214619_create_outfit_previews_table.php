<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outfit_previews', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('outfit_id')->constrained();
            $table->foreignUlid('avatar_version_id')->nullable()->constrained();
            $table->string('mode', 32);
            $table->string('status', 32);
            $table->string('cache_key', 64)->unique();
            $table->string('provider', 64)->nullable();
            $table->unsignedInteger('cost_cents')->default(0);
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['outfit_id', 'mode']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outfit_previews');
    }
};

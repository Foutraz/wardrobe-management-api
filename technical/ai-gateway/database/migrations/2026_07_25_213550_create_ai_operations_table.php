<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_operations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->nullable()->constrained();
            $table->string('kind', 32);
            $table->string('provider', 64);
            $table->string('status', 32);
            $table->string('subject_type')->nullable();
            $table->ulid('subject_id')->nullable();
            $table->unsignedInteger('cost_cents')->default(0);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'kind', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['kind', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_operations');
    }
};

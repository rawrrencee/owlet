<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('migration_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('legacy_id');
            $table->unsignedBigInteger('owlet_id')->nullable();
            $table->foreignId('migration_run_id')->nullable()->constrained('migration_runs')->cascadeOnDelete();
            $table->enum('status', ['pending', 'success', 'failed', 'skipped'])->default('pending');
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['model_type', 'legacy_id']);
            $table->index(['model_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('migration_logs');
    }
};

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
        Schema::create('migration_runs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['running', 'completed', 'failed', 'cancelled'])->default('running');
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('migrated_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->text('error_message')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['model_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('migration_runs');
    }
};

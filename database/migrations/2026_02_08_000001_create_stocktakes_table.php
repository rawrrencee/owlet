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
        Schema::create('stocktakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('in_progress');
            $table->boolean('has_issues')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->text('notes')->nullable();

            // Audit trail fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('previous_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('previous_updated_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['store_id', 'status']);
            $table->index(['employee_id', 'store_id']);
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocktakes');
    }
};

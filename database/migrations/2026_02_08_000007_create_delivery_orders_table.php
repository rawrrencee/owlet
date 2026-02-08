<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('store_id_from')->constrained('stores')->restrictOnDelete();
            $table->foreignId('store_id_to')->constrained('stores')->restrictOnDelete();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();

            // Audit trail fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('previous_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('previous_updated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id_from', 'status']);
            $table->index(['store_id_to', 'status']);
            $table->index('status');
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};

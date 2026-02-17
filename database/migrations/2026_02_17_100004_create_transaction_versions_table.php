<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->integer('version_number');
            $table->string('change_type');
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->string('change_summary');
            $table->json('snapshot_items');
            $table->json('snapshot_payments');
            $table->json('snapshot_totals');
            $table->json('diff_data')->nullable();
            $table->timestamp('created_at');

            // Indexes
            $table->index('transaction_id');
            $table->unique(['transaction_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_versions');
    }
};

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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('subcategory_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->string('product_number')->unique();
            $table->string('barcode')->nullable()->index();
            $table->string('supplier_number')->nullable();
            $table->text('description')->nullable();
            $table->string('cost_price_remarks')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_filename')->nullable();
            $table->string('image_mime_type', 100)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->enum('weight_unit', ['kg', 'g', 'lb', 'oz'])->default('kg');
            $table->boolean('is_active')->default(true)->index();

            // Audit trail fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('previous_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('previous_updated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Additional indexes
            $table->index('brand_id');
            $table->index('category_id');
            $table->index('subcategory_id');
            $table->index('supplier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

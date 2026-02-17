<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            // Product snapshots
            $table->string('product_name');
            $table->string('product_number');
            $table->string('variant_name')->nullable();
            $table->string('barcode')->nullable();

            $table->integer('quantity');
            $table->decimal('cost_price', 12, 4)->nullable();
            $table->decimal('unit_price', 12, 4);

            // Offer snapshot
            $table->foreignId('offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->string('offer_name')->nullable();
            $table->string('offer_discount_type')->nullable();
            $table->decimal('offer_discount_amount', 12, 4)->default(0);
            $table->boolean('offer_is_combinable')->nullable();

            // Customer discount
            $table->decimal('customer_discount_percentage', 5, 2)->nullable();
            $table->decimal('customer_discount_amount', 12, 4)->default(0);

            // Line totals
            $table->decimal('line_subtotal', 12, 4);
            $table->decimal('line_discount', 12, 4)->default(0);
            $table->decimal('line_total', 12, 4);

            // Refund tracking
            $table->boolean('is_refunded')->default(false);
            $table->string('refund_reason')->nullable();

            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('transaction_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 19, 4);
            $table->integer('sort_order')->default(0);

            // Offer snapshot
            $table->foreignId('offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->string('offer_name')->nullable();
            $table->string('offer_discount_type')->nullable();
            $table->decimal('offer_discount_amount', 19, 4)->nullable();
            $table->boolean('offer_is_combinable')->nullable();

            // Customer discount snapshot
            $table->decimal('customer_discount_percentage', 5, 2)->nullable();
            $table->decimal('customer_discount_amount', 19, 4)->nullable();

            // Computed
            $table->decimal('line_subtotal', 19, 4);
            $table->decimal('line_discount', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};

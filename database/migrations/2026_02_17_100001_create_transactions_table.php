<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('draft');
            $table->timestamp('checkout_date')->nullable();

            // Totals
            $table->decimal('subtotal', 12, 4)->default(0);
            $table->decimal('offer_discount', 12, 4)->default(0);
            $table->decimal('bundle_discount', 12, 4)->default(0);
            $table->decimal('minimum_spend_discount', 12, 4)->default(0);
            $table->decimal('customer_discount', 12, 4)->default(0);
            $table->decimal('manual_discount', 12, 4)->default(0);

            // Tax
            $table->decimal('tax_percentage', 5, 2)->nullable();
            $table->boolean('tax_inclusive')->default(false);
            $table->decimal('tax_amount', 12, 4)->default(0);

            // Final amounts
            $table->decimal('total', 12, 4)->default(0);
            $table->decimal('amount_paid', 12, 4)->default(0);
            $table->decimal('refund_amount', 12, 4)->default(0);
            $table->decimal('balance_due', 12, 4)->default(0);
            $table->decimal('change_amount', 12, 4)->default(0);

            $table->text('comments')->nullable();

            // Bundle offer snapshot
            $table->foreignId('bundle_offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->string('bundle_offer_name')->nullable();

            // Minimum spend offer snapshot
            $table->foreignId('minimum_spend_offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->string('minimum_spend_offer_name')->nullable();

            // Customer discount snapshot
            $table->decimal('customer_discount_percentage', 5, 2)->nullable();

            $table->integer('version_count')->default(0);

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('previous_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('previous_updated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('store_id');
            $table->index('employee_id');
            $table->index('customer_id');
            $table->index('status');
            $table->index('checkout_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number', 30)->unique();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('status', 20)->default('draft');

            // Company display toggles
            $table->boolean('show_company_logo')->default(true);
            $table->boolean('show_company_address')->default(true);
            $table->boolean('show_company_uen')->default(true);

            // Tax config
            $table->string('tax_mode', 20)->default('none'); // none, store, manual
            $table->foreignId('tax_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->decimal('tax_percentage', 5, 2)->nullable();
            $table->boolean('tax_inclusive')->default(false);

            // Optional sections
            $table->text('terms_and_conditions')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('external_notes')->nullable();
            $table->text('payment_terms')->nullable();
            $table->date('validity_date')->nullable();

            // Customer discount snapshot
            $table->decimal('customer_discount_percentage', 5, 2)->nullable();

            // Future: shareable link
            $table->string('share_token', 64)->nullable()->unique();
            $table->string('share_password_hash')->nullable();

            // Future: signature
            $table->timestamp('signed_at')->nullable();
            $table->text('signature_data')->nullable();
            $table->string('signed_by_name')->nullable();
            $table->string('signed_by_ip')->nullable();

            // Lifecycle
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('previous_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('previous_updated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_payment_mode', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('payment_mode_id')->constrained('payment_modes')->restrictOnDelete();
            $table->unique(['quotation_id', 'payment_mode_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_payment_mode');
    }
};

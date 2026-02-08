<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->decimal('discount_amount', 12, 4)->nullable();
            $table->decimal('max_discount_amount', 12, 4)->nullable();

            $table->unique(['offer_id', 'currency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_amounts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->integer('quantity');
            $table->integer('received_quantity')->nullable();
            $table->text('correction_note')->nullable();
            $table->timestamps();

            $table->unique(['delivery_order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_items');
    }
};

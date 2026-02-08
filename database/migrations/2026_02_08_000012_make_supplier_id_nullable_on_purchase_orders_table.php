<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->foreignId('supplier_id')->nullable()->change();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->foreignId('supplier_id')->nullable(false)->change();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->foreignId('delivery_order_id')->nullable()->after('stocktake_id')->constrained()->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->after('delivery_order_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_order_id');
            $table->dropConstrainedForeignId('purchase_order_id');
        });
    }
};

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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('parent_product_id')
                ->nullable()
                ->after('id')
                ->constrained('products')
                ->nullOnDelete();
            $table->string('variant_name')->nullable()->after('product_name');

            $table->index('parent_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['parent_product_id']);
            $table->dropIndex(['parent_product_id']);
            $table->dropColumn(['parent_product_id', 'variant_name']);
        });
    }
};

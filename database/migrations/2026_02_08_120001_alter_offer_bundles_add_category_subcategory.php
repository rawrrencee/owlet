<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_bundles', function (Blueprint $table) {
            // Make product_id nullable (was required)
            $table->foreignId('product_id')->nullable()->change();

            // Add category and subcategory FK columns
            $table->foreignId('category_id')->nullable()->after('product_id')
                ->constrained()->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->after('category_id')
                ->constrained()->nullOnDelete();

            // Unique constraints for category and subcategory entries
            $table->unique(['offer_id', 'category_id']);
            $table->unique(['offer_id', 'subcategory_id']);
        });
    }

    public function down(): void
    {
        Schema::table('offer_bundles', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['subcategory_id']);
            $table->dropUnique(['offer_id', 'category_id']);
            $table->dropUnique(['offer_id', 'subcategory_id']);
            $table->dropColumn(['category_id', 'subcategory_id']);

            // Restore product_id to non-nullable
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }
};

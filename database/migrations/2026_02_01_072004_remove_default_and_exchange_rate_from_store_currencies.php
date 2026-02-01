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
        Schema::table('store_currencies', function (Blueprint $table) {
            $table->dropColumn(['is_default', 'exchange_rate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_currencies', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('currency_id');
            $table->decimal('exchange_rate', 16, 6)->nullable()->after('is_default');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('manual_discount_type')->nullable()->after('manual_discount');
            $table->decimal('manual_discount_value', 12, 4)->nullable()->after('manual_discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['manual_discount_type', 'manual_discount_value']);
        });
    }
};

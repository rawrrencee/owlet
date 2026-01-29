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
        Schema::table('timecards', function (Blueprint $table) {
            $table->boolean('is_incomplete')->default(false)->after('status');
            $table->boolean('is_inaccurate')->default(false)->after('is_incomplete');
            $table->timestamp('user_provided_end_date')->nullable()->after('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timecards', function (Blueprint $table) {
            $table->dropColumn(['is_incomplete', 'is_inaccurate', 'user_provided_end_date']);
        });
    }
};

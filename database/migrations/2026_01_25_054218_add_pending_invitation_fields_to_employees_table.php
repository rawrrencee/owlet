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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('pending_email')->nullable()->after('notes');
            $table->string('pending_role')->nullable()->after('pending_email');
            $table->index('pending_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['pending_email']);
            $table->dropColumn(['pending_email', 'pending_role']);
        });
    }
};

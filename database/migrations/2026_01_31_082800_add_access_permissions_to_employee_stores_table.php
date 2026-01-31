<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_stores', function (Blueprint $table) {
            $table->json('access_permissions')->nullable()->after('permissions');
        });
    }

    public function down(): void
    {
        Schema::table('employee_stores', function (Blueprint $table) {
            $table->dropColumn('access_permissions');
        });
    }
};

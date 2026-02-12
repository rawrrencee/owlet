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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('registration_number', 50)->nullable()->after('company_name');
            $table->string('tax_registration_number', 50)->nullable()->after('registration_number');
            $table->string('city', 255)->nullable()->after('address_2');
            $table->string('state', 255)->nullable()->after('city');
            $table->string('postal_code', 20)->nullable()->after('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['registration_number', 'tax_registration_number', 'city', 'state', 'postal_code']);
        });
    }
};

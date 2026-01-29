<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add country_id to companies table
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('address_2')->constrained()->nullOnDelete();
        });

        // Add country_id to stores table
        Schema::table('stores', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('address_2')->constrained()->nullOnDelete();
        });

        // Add country_id and nationality_id to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('race')->constrained()->nullOnDelete();
            $table->foreignId('nationality_id')->nullable()->after('country_id')->constrained('countries')->nullOnDelete();
        });

        // Add country_id and nationality_id to employees table (keeping old string columns for now)
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('country')->constrained()->nullOnDelete();
            $table->foreignId('nationality_id')->nullable()->after('nationality')->constrained('countries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('nationality_id');
            $table->dropConstrainedForeignId('country_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('nationality_id');
            $table->dropConstrainedForeignId('country_id');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
        });
    }
};

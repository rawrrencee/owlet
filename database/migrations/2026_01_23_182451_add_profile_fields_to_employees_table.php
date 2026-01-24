<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Identification
            $table->string('nric')->nullable()->after('employee_number');
            $table->string('chinese_name')->nullable()->after('last_name');

            // Address
            $table->string('address_1')->nullable()->after('mobile');
            $table->string('address_2')->nullable()->after('address_1');
            $table->string('city')->nullable()->after('address_2');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state');
            $table->string('country')->nullable()->after('postal_code');

            // Personal details
            $table->string('race')->nullable()->after('gender');
            $table->string('nationality')->nullable()->after('race');
            $table->string('residency_status')->nullable()->after('nationality');
            $table->date('pr_conversion_date')->nullable()->after('residency_status');

            // Emergency contact
            $table->string('emergency_name')->nullable()->after('pr_conversion_date');
            $table->string('emergency_relationship')->nullable()->after('emergency_name');
            $table->string('emergency_contact')->nullable()->after('emergency_relationship');
            $table->string('emergency_address_1')->nullable()->after('emergency_contact');
            $table->string('emergency_address_2')->nullable()->after('emergency_address_1');

            // Bank details
            $table->string('bank_name')->nullable()->after('emergency_address_2');
            $table->string('bank_account_number')->nullable()->after('bank_name');

            // Image
            $table->string('image_url')->nullable()->after('bank_account_number');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'nric',
                'chinese_name',
                'address_1',
                'address_2',
                'city',
                'state',
                'postal_code',
                'country',
                'race',
                'nationality',
                'residency_status',
                'pr_conversion_date',
                'emergency_name',
                'emergency_relationship',
                'emergency_contact',
                'emergency_address_1',
                'emergency_address_2',
                'bank_name',
                'bank_account_number',
                'image_url',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_requests', function (Blueprint $table) {
            $table->id();

            // Personal
            $table->string('first_name');
            $table->string('last_name');
            $table->string('chinese_name')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('race')->nullable();

            // Identity
            $table->string('nric')->nullable();
            $table->string('nationality')->nullable();
            $table->foreignId('nationality_id')->nullable()->constrained('countries');
            $table->string('residency_status')->nullable();

            // Address
            $table->string('address_1')->nullable();
            $table->string('address_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries');

            // Emergency
            $table->string('emergency_name')->nullable();
            $table->string('emergency_relationship')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_address_1')->nullable();
            $table->string('emergency_address_2')->nullable();

            // Banking
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();

            // Additional
            $table->text('notes')->nullable();
            $table->string('profile_picture')->nullable();

            // Status tracking
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_requests');
    }
};

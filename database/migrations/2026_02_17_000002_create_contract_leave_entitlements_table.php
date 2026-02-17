<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_leave_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_contract_id')->constrained('employee_contracts')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->decimal('entitled_days', 5, 1)->default(0);
            $table->decimal('taken_days', 5, 1)->default(0);
            $table->timestamps();

            $table->unique(['employee_contract_id', 'leave_type_id'], 'contract_leave_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_leave_entitlements');
    }
};

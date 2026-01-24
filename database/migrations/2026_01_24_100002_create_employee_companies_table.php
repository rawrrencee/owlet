<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('levy_amount', 19, 4)->default(0);
            $table->string('status', 10)->default('FT'); // FT, PT, CT, CA
            $table->boolean('include_shg_donations')->default(false);
            $table->date('commencement_date');
            $table->date('left_date')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'company_id', 'commencement_date'], 'employee_company_commencement_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_companies');
    }
};

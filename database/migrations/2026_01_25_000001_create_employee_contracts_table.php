<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('salary_amount', 19, 4)->default(0);
            $table->unsignedTinyInteger('annual_leave_entitled')->default(0);
            $table->unsignedTinyInteger('annual_leave_taken')->default(0);
            $table->unsignedTinyInteger('sick_leave_entitled')->default(0);
            $table->unsignedTinyInteger('sick_leave_taken')->default(0);
            $table->string('external_document_url')->nullable();
            $table->string('document_path')->nullable();
            $table->string('document_filename')->nullable();
            $table->string('document_mime_type')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_contracts');
    }
};

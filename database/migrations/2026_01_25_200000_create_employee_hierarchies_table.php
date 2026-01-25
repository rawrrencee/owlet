<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_hierarchies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('subordinate_id')->constrained('employees')->onDelete('cascade');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['manager_id', 'subordinate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_hierarchies');
    }
};

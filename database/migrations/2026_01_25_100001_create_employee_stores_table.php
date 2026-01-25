<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->boolean('active')->default(true);
            $table->json('permissions')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_stores');
    }
};

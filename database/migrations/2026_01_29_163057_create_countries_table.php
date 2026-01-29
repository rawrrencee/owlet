<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 2)->unique();
            $table->string('code_3', 3)->nullable();
            $table->string('nationality_name');
            $table->string('phone_code', 10)->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(999);
            $table->timestamps();

            $table->index(['active', 'sort_order', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};

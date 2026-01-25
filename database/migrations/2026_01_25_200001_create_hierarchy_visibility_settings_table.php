<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hierarchy_visibility_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->constrained('employees')->onDelete('cascade');
            $table->json('visible_sections')->nullable();
            $table->timestamps();

            $table->unique('manager_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hierarchy_visibility_settings');
    }
};

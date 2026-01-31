<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('brand_name');
            $table->string('brand_code', 4)->unique();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address_1')->nullable();
            $table->string('address_2')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->string('mobile_number', 50)->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('logo_filename')->nullable();
            $table->string('logo_mime_type')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};

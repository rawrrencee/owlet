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
        Schema::create('timecard_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timecard_id')->constrained()->onDelete('cascade');
            $table->string('type'); // WORK, BREAK
            $table->timestamp('start_date');
            $table->timestamp('end_date')->nullable();
            $table->decimal('hours', 8, 2)->default(0);
            $table->timestamps();

            $table->index(['timecard_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timecard_details');
    }
};

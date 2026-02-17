<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('requires_balance')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('previous_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('previous_updated_at')->nullable();

            $table->timestamps();
        });

        // Seed default leave types
        DB::table('leave_types')->insert([
            [
                'name' => 'Annual Leave',
                'code' => 'annual',
                'description' => 'Paid annual leave entitlement',
                'color' => '#4CAF50',
                'requires_balance' => true,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'sick',
                'description' => 'Paid sick leave entitlement',
                'color' => '#FF9800',
                'requires_balance' => true,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Unpaid Leave',
                'code' => 'unpaid',
                'description' => 'Unpaid leave of absence',
                'color' => '#9E9E9E',
                'requires_balance' => false,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables to add audit trail fields to.
     * Excludes timecards which needs special handling.
     */
    protected array $tables = [
        'employees',
        'customers',
        'companies',
        'stores',
        'brands',
        'categories',
        'subcategories',
        'suppliers',
        'designations',
        'employee_contracts',
        'employee_insurances',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add audit fields to standard tables
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
                $table->foreignId('previous_updated_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
                $table->timestamp('previous_updated_at')->nullable()->after('previous_updated_by');
            });
        }

        // Special handling for timecards table:
        // Currently has created_by/updated_by pointing to employees table
        // Need to migrate to users table and add previous_* fields
        Schema::table('timecards', function (Blueprint $table) {
            // Drop existing foreign key constraints
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            // Drop existing columns
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('timecards', function (Blueprint $table) {
            // Re-add columns with foreign keys to users table
            $table->foreignId('created_by')->nullable()->after('hours_worked')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->foreignId('previous_updated_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->timestamp('previous_updated_at')->nullable()->after('previous_updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove audit fields from standard tables
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropForeign(['updated_by']);
                $table->dropForeign(['previous_updated_by']);
                $table->dropColumn(['created_by', 'updated_by', 'previous_updated_by', 'previous_updated_at']);
            });
        }

        // Restore timecards to original structure (pointing to employees)
        Schema::table('timecards', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['previous_updated_by']);
            $table->dropColumn(['created_by', 'updated_by', 'previous_updated_by', 'previous_updated_at']);
        });

        Schema::table('timecards', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('employees')->nullOnDelete();
        });
    }
};

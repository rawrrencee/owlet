<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Get annual and sick leave type IDs
        $annualLeaveTypeId = DB::table('leave_types')->where('code', 'annual')->value('id');
        $sickLeaveTypeId = DB::table('leave_types')->where('code', 'sick')->value('id');

        if (! $annualLeaveTypeId || ! $sickLeaveTypeId) {
            throw new \RuntimeException('Default leave types not found. Run the create_leave_types_table migration first.');
        }

        // Migrate existing data
        $contracts = DB::table('employee_contracts')->select([
            'id',
            'annual_leave_entitled',
            'annual_leave_taken',
            'sick_leave_entitled',
            'sick_leave_taken',
        ])->get();

        foreach ($contracts as $contract) {
            // Migrate annual leave
            if ($contract->annual_leave_entitled > 0 || $contract->annual_leave_taken > 0) {
                DB::table('contract_leave_entitlements')->insert([
                    'employee_contract_id' => $contract->id,
                    'leave_type_id' => $annualLeaveTypeId,
                    'entitled_days' => $contract->annual_leave_entitled ?? 0,
                    'taken_days' => $contract->annual_leave_taken ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Migrate sick leave
            if ($contract->sick_leave_entitled > 0 || $contract->sick_leave_taken > 0) {
                DB::table('contract_leave_entitlements')->insert([
                    'employee_contract_id' => $contract->id,
                    'leave_type_id' => $sickLeaveTypeId,
                    'entitled_days' => $contract->sick_leave_entitled ?? 0,
                    'taken_days' => $contract->sick_leave_taken ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Drop old columns
        Schema::table('employee_contracts', function (Blueprint $table) {
            $table->dropColumn([
                'annual_leave_entitled',
                'annual_leave_taken',
                'sick_leave_entitled',
                'sick_leave_taken',
            ]);
        });
    }

    public function down(): void
    {
        // Re-add old columns
        Schema::table('employee_contracts', function (Blueprint $table) {
            $table->unsignedTinyInteger('annual_leave_entitled')->default(0)->after('salary_amount');
            $table->unsignedTinyInteger('annual_leave_taken')->default(0)->after('annual_leave_entitled');
            $table->unsignedTinyInteger('sick_leave_entitled')->default(0)->after('annual_leave_taken');
            $table->unsignedTinyInteger('sick_leave_taken')->default(0)->after('sick_leave_entitled');
        });

        // Migrate data back
        $annualLeaveTypeId = DB::table('leave_types')->where('code', 'annual')->value('id');
        $sickLeaveTypeId = DB::table('leave_types')->where('code', 'sick')->value('id');

        $entitlements = DB::table('contract_leave_entitlements')->get();

        foreach ($entitlements as $entitlement) {
            if ($entitlement->leave_type_id === $annualLeaveTypeId) {
                DB::table('employee_contracts')
                    ->where('id', $entitlement->employee_contract_id)
                    ->update([
                        'annual_leave_entitled' => (int) $entitlement->entitled_days,
                        'annual_leave_taken' => (int) $entitlement->taken_days,
                    ]);
            } elseif ($entitlement->leave_type_id === $sickLeaveTypeId) {
                DB::table('employee_contracts')
                    ->where('id', $entitlement->employee_contract_id)
                    ->update([
                        'sick_leave_entitled' => (int) $entitlement->entitled_days,
                        'sick_leave_taken' => (int) $entitlement->taken_days,
                    ]);
            }
        }
    }
};

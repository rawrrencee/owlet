<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Store;
use App\Models\Timecard;
use App\Models\TimecardDetail;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class TimecardSeeder extends Seeder
{
    /**
     * Seed timecards with work/break details using Faker.
     */
    public function run(): void
    {
        $this->command->info('Seeding timecards...');

        $faker = Faker::create();
        $employees = Employee::with('stores')->get();

        if ($employees->isEmpty()) {
            $this->command->warn('  No employees found. Skipping...');

            return;
        }

        $timecardsPerEmployee = config('seeders.counts.timecards_per_employee', 3);
        $createdCount = 0;

        foreach ($employees as $employee) {
            // Get stores assigned to this employee
            $stores = $employee->stores;
            if ($stores->isEmpty()) {
                $stores = Store::all();
            }

            if ($stores->isEmpty()) {
                continue;
            }

            // Create timecards spread over past 30 days
            for ($i = 1; $i <= $timecardsPerEmployee; $i++) {
                $store = $faker->randomElement($stores->toArray());

                // Random date within past 30 days (skip today to avoid conflicts)
                $date = Carbon::today()->subDays($faker->numberBetween(1, 30));

                // Work shift: 8-10 hours starting between 8am-10am
                $startHour = $faker->numberBetween(8, 10);
                $startDate = $date->copy()->setHour($startHour)->setMinute($faker->randomElement([0, 15, 30]));
                $workHours = $faker->randomFloat(2, 7, 10);
                $endDate = $startDate->copy()->addMinutes((int) ($workHours * 60));

                $timecard = Timecard::create([
                    'employee_id' => $employee->id,
                    'store_id' => $store['id'],
                    'status' => Timecard::STATUS_COMPLETED,
                    'is_incomplete' => false,
                    'is_inaccurate' => $faker->boolean(10), // 10% chance of being inaccurate
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'user_provided_end_date' => null,
                    'hours_worked' => $workHours,
                    'created_by' => $employee->id,
                    'updated_by' => $employee->id,
                ]);

                // Create work and break details
                $this->createTimecardDetails($faker, $timecard, $startDate, $endDate);

                $createdCount++;
            }
        }

        $this->command->info("  Created {$createdCount} timecards with details.");
    }

    /**
     * Create work and break details for a timecard.
     */
    private function createTimecardDetails($faker, Timecard $timecard, Carbon $startDate, Carbon $endDate): void
    {
        $currentTime = $startDate->copy();

        // Morning work session (before lunch)
        $morningWorkEnd = $currentTime->copy()->addHours($faker->numberBetween(3, 4));
        if ($morningWorkEnd->gt($endDate)) {
            $morningWorkEnd = $endDate->copy();
        }

        $morningHours = $currentTime->diffInMinutes($morningWorkEnd) / 60;
        TimecardDetail::create([
            'timecard_id' => $timecard->id,
            'type' => TimecardDetail::TYPE_WORK,
            'start_date' => $currentTime,
            'end_date' => $morningWorkEnd,
            'hours' => round($morningHours, 2),
        ]);

        if ($morningWorkEnd->gte($endDate)) {
            return;
        }

        $currentTime = $morningWorkEnd->copy();

        // Lunch break (30-60 minutes)
        $breakDuration = $faker->numberBetween(30, 60);
        $lunchEnd = $currentTime->copy()->addMinutes($breakDuration);
        if ($lunchEnd->gt($endDate)) {
            $lunchEnd = $endDate->copy();
        }

        $breakHours = $currentTime->diffInMinutes($lunchEnd) / 60;
        TimecardDetail::create([
            'timecard_id' => $timecard->id,
            'type' => TimecardDetail::TYPE_BREAK,
            'start_date' => $currentTime,
            'end_date' => $lunchEnd,
            'hours' => round($breakHours, 2),
        ]);

        if ($lunchEnd->gte($endDate)) {
            return;
        }

        $currentTime = $lunchEnd->copy();

        // Afternoon work session (until end)
        $afternoonHours = $currentTime->diffInMinutes($endDate) / 60;
        TimecardDetail::create([
            'timecard_id' => $timecard->id,
            'type' => TimecardDetail::TYPE_WORK,
            'start_date' => $currentTime,
            'end_date' => $endDate,
            'hours' => round($afternoonHours, 2),
        ]);
    }
}

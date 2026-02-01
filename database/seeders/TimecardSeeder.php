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
        $allStores = Store::all();

        if ($employees->isEmpty()) {
            $this->command->warn('  No employees found. Skipping...');

            return;
        }

        $timecardsPerEmployee = config('seeders.counts.timecards_per_employee', 10);
        $createdCount = 0;
        $batchSize = 50;
        $timecards = [];

        $this->command->line("  Creating timecards for {$employees->count()} employees...");

        foreach ($employees as $employee) {
            // Get stores assigned to this employee
            $stores = $employee->stores;
            if ($stores->isEmpty()) {
                $stores = $allStores;
            }

            if ($stores->isEmpty()) {
                continue;
            }

            $storeIds = $stores->pluck('id')->toArray();

            // Create timecards spread over past 90 days
            for ($i = 1; $i <= $timecardsPerEmployee; $i++) {
                $storeId = $faker->randomElement($storeIds);

                // Random date within past 90 days (skip today to avoid conflicts)
                $date = Carbon::today()->subDays($faker->numberBetween(1, 90));

                // Work shift: 8-10 hours starting between 8am-10am
                $startHour = $faker->numberBetween(8, 10);
                $startDate = $date->copy()->setHour($startHour)->setMinute($faker->randomElement([0, 15, 30]));
                $workHours = $faker->randomFloat(2, 7, 10);
                $endDate = $startDate->copy()->addMinutes((int) ($workHours * 60));

                $timecards[] = [
                    'employee_id' => $employee->id,
                    'store_id' => $storeId,
                    'status' => Timecard::STATUS_COMPLETED,
                    'is_incomplete' => false,
                    'is_inaccurate' => $faker->boolean(10),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'user_provided_end_date' => null,
                    'hours_worked' => $workHours,
                    'created_by' => $employee->id,
                    'updated_by' => $employee->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($timecards) >= $batchSize) {
                    Timecard::insert($timecards);
                    $createdCount += count($timecards);
                    $timecards = [];
                }
            }
        }

        // Insert remaining
        if (! empty($timecards)) {
            Timecard::insert($timecards);
            $createdCount += count($timecards);
        }

        $this->command->info("  Created {$createdCount} timecards.");

        // Add timecard details
        $this->seedTimecardDetails($faker);
    }

    /**
     * Seed timecard details (work/break periods).
     */
    private function seedTimecardDetails($faker): void
    {
        $this->command->line('  Adding timecard details...');

        $timecards = Timecard::all();
        $batchSize = 100;
        $details = [];
        $count = 0;

        foreach ($timecards as $timecard) {
            $startDate = Carbon::parse($timecard->start_date);
            $endDate = Carbon::parse($timecard->end_date);
            $currentTime = $startDate->copy();

            // Morning work session (before lunch)
            $morningWorkEnd = $currentTime->copy()->addHours($faker->numberBetween(3, 4));
            if ($morningWorkEnd->gt($endDate)) {
                $morningWorkEnd = $endDate->copy();
            }

            $morningHours = $currentTime->diffInMinutes($morningWorkEnd) / 60;
            $details[] = [
                'timecard_id' => $timecard->id,
                'type' => TimecardDetail::TYPE_WORK,
                'start_date' => $currentTime,
                'end_date' => $morningWorkEnd,
                'hours' => round($morningHours, 2),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($morningWorkEnd->gte($endDate)) {
                if (count($details) >= $batchSize) {
                    TimecardDetail::insert($details);
                    $count += count($details);
                    $details = [];
                }

                continue;
            }

            $currentTime = $morningWorkEnd->copy();

            // Lunch break (30-60 minutes)
            $breakDuration = $faker->numberBetween(30, 60);
            $lunchEnd = $currentTime->copy()->addMinutes($breakDuration);
            if ($lunchEnd->gt($endDate)) {
                $lunchEnd = $endDate->copy();
            }

            $breakHours = $currentTime->diffInMinutes($lunchEnd) / 60;
            $details[] = [
                'timecard_id' => $timecard->id,
                'type' => TimecardDetail::TYPE_BREAK,
                'start_date' => $currentTime,
                'end_date' => $lunchEnd,
                'hours' => round($breakHours, 2),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($lunchEnd->gte($endDate)) {
                if (count($details) >= $batchSize) {
                    TimecardDetail::insert($details);
                    $count += count($details);
                    $details = [];
                }

                continue;
            }

            $currentTime = $lunchEnd->copy();

            // Afternoon work session (until end)
            $afternoonHours = $currentTime->diffInMinutes($endDate) / 60;
            $details[] = [
                'timecard_id' => $timecard->id,
                'type' => TimecardDetail::TYPE_WORK,
                'start_date' => $currentTime,
                'end_date' => $endDate,
                'hours' => round($afternoonHours, 2),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($details) >= $batchSize) {
                TimecardDetail::insert($details);
                $count += count($details);
                $details = [];
            }
        }

        // Insert remaining
        if (! empty($details)) {
            TimecardDetail::insert($details);
            $count += count($details);
        }

        $this->command->info("  Created {$count} timecard details.");
    }
}

<?php

namespace App\Services;

use App\Enums\NotificationEventType;
use App\Mail\TimecardNotificationMail;
use App\Models\Employee;
use App\Models\NotificationRecipient;
use App\Models\Store;
use App\Models\Timecard;
use App\Models\TimecardDetail;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TimecardService
{
    /**
     * Clock in an employee at a store.
     */
    public function clockIn(Employee $employee, Store $store, ?Employee $createdBy = null): Timecard
    {
        $timecard = DB::transaction(function () use ($employee, $store, $createdBy) {
            $now = now();

            $timecard = Timecard::create([
                'employee_id' => $employee->id,
                'store_id' => $store->id,
                'status' => Timecard::STATUS_IN_PROGRESS,
                'start_date' => $now,
                'created_by' => $createdBy?->id ?? $employee->id,
            ]);

            TimecardDetail::create([
                'timecard_id' => $timecard->id,
                'type' => TimecardDetail::TYPE_WORK,
                'start_date' => $now,
            ]);

            return $timecard->load('details', 'store', 'employee');
        });

        $this->sendNotification($timecard, 'Clock In');

        return $timecard;
    }

    /**
     * Clock out from a timecard.
     */
    public function clockOut(Timecard $timecard, ?Employee $updatedBy = null): Timecard
    {
        $result = DB::transaction(function () use ($timecard, $updatedBy) {
            $now = now();

            // End all open details
            $this->closeOpenDetails($timecard, $now);

            // Calculate total work hours
            $totalWorkHours = $this->calculateWorkHours($timecard);

            $timecard->update([
                'status' => Timecard::STATUS_COMPLETED,
                'end_date' => $now,
                'hours_worked' => $totalWorkHours,
                'updated_by' => $updatedBy?->id,
            ]);

            return $timecard->fresh(['details', 'store', 'employee']);
        });

        $this->sendNotification($result, 'Clock Out');

        return $result;
    }

    /**
     * Start a break on a timecard.
     */
    public function startBreak(Timecard $timecard, ?Employee $updatedBy = null): Timecard
    {
        return DB::transaction(function () use ($timecard, $updatedBy) {
            $now = now();

            // End current work detail
            $this->closeOpenDetails($timecard, $now);

            // Create break detail
            TimecardDetail::create([
                'timecard_id' => $timecard->id,
                'type' => TimecardDetail::TYPE_BREAK,
                'start_date' => $now,
            ]);

            $timecard->update(['updated_by' => $updatedBy?->id]);

            return $timecard->fresh(['details', 'store', 'employee']);
        });
    }

    /**
     * End a break on a timecard.
     */
    public function endBreak(Timecard $timecard, ?Employee $updatedBy = null): Timecard
    {
        return DB::transaction(function () use ($timecard, $updatedBy) {
            $now = now();

            // End current break detail
            $this->closeOpenDetails($timecard, $now);

            // Create new work detail
            TimecardDetail::create([
                'timecard_id' => $timecard->id,
                'type' => TimecardDetail::TYPE_WORK,
                'start_date' => $now,
            ]);

            $timecard->update(['updated_by' => $updatedBy?->id]);

            return $timecard->fresh(['details', 'store', 'employee']);
        });
    }

    /**
     * Close all open details on a timecard.
     */
    private function closeOpenDetails(Timecard $timecard, CarbonInterface $endTime): void
    {
        $openDetails = $timecard->details()->whereNull('end_date')->get();

        foreach ($openDetails as $detail) {
            $hours = $detail->start_date->diffInMinutes($endTime) / 60;
            $detail->update([
                'end_date' => $endTime,
                'hours' => round($hours, 2),
            ]);
        }
    }

    /**
     * Calculate total work hours for a timecard.
     */
    public function calculateWorkHours(Timecard $timecard): float
    {
        return (float) $timecard->workDetails()
            ->whereNotNull('end_date')
            ->sum('hours');
    }

    /**
     * Recalculate hours for a timecard based on its details.
     */
    public function recalculateHours(Timecard $timecard): Timecard
    {
        $totalWorkHours = $this->calculateWorkHours($timecard);

        $timecard->update(['hours_worked' => $totalWorkHours]);

        return $timecard->fresh();
    }

    /**
     * Get current in-progress timecard for an employee.
     */
    public function getCurrentTimecard(Employee $employee, ?Store $store = null): ?Timecard
    {
        $query = Timecard::where('employee_id', $employee->id)
            ->inProgress()
            ->whereDate('start_date', today())
            ->with(['details', 'store', 'employee']);

        if ($store) {
            $query->where('store_id', $store->id);
        }

        return $query->first();
    }

    /**
     * Get monthly statistics for an employee.
     *
     * @return array{total_hours: float, days_worked: int, daily_average: float}
     */
    public function getMonthlyStats(Employee $employee, CarbonInterface $month): array
    {
        $timecards = Timecard::where('employee_id', $employee->id)
            ->forMonth($month)
            ->get();

        $totalHours = (float) $timecards->sum('hours_worked');
        $daysWorked = $timecards->groupBy(fn (Timecard $t) => $t->start_date->toDateString())->count();
        $dailyAverage = $daysWorked > 0 ? round($totalHours / $daysWorked, 2) : 0;

        return [
            'total_hours' => round($totalHours, 2),
            'days_worked' => $daysWorked,
            'daily_average' => $dailyAverage,
        ];
    }

    /**
     * Get monthly timecards for an employee grouped by date.
     *
     * @return Collection<string, array{date: string, stores: array, total_hours: float}>
     */
    public function getMonthlyTimecards(Employee $employee, CarbonInterface $month): Collection
    {
        $timecards = Timecard::where('employee_id', $employee->id)
            ->forMonth($month)
            ->with(['store'])
            ->orderBy('start_date')
            ->get();

        return $timecards->groupBy(fn (Timecard $t) => $t->start_date->toDateString())
            ->map(function (Collection $dayTimecards, string $date) {
                $totalHours = $dayTimecards->sum('hours_worked');
                $stores = $dayTimecards->map(fn (Timecard $t) => [
                    'id' => $t->store_id,
                    'name' => $t->store->store_name,
                    'hours' => (float) $t->hours_worked,
                    'status' => $t->status,
                ])->values()->all();

                return [
                    'date' => $date,
                    'stores' => $stores,
                    'total_hours' => (float) $totalHours,
                ];
            });
    }

    /**
     * Get timecards for an employee on a specific date.
     */
    public function getTimecardsByDate(Employee $employee, CarbonInterface $date): Collection
    {
        return Timecard::where('employee_id', $employee->id)
            ->forDate($date)
            ->with(['store', 'details'])
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Get admin monthly overview of all timecards.
     *
     * @return Collection<string, array{date: string, employee_count: int, total_hours: float}>
     */
    public function getAdminMonthlyOverview(CarbonInterface $month): Collection
    {
        $timecards = Timecard::forMonth($month)
            ->with(['employee'])
            ->get();

        return $timecards->groupBy(fn (Timecard $t) => $t->start_date->toDateString())
            ->map(function (Collection $dayTimecards, string $date) {
                $employeeIds = $dayTimecards->pluck('employee_id')->unique()->count();
                $totalHours = $dayTimecards->sum('hours_worked');

                return [
                    'date' => $date,
                    'employee_count' => $employeeIds,
                    'total_hours' => (float) $totalHours,
                ];
            });
    }

    /**
     * Get all timecards for a specific date (admin view).
     */
    public function getAdminTimecardsByDate(CarbonInterface $date): Collection
    {
        return Timecard::forDate($date)
            ->with(['employee', 'store', 'details'])
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Get or create a timecard for admin entry.
     */
    public function getOrCreateTimecard(Employee $employee, Store $store, CarbonInterface $date, Employee $creator): Timecard
    {
        $existing = Timecard::where('employee_id', $employee->id)
            ->where('store_id', $store->id)
            ->forDate($date)
            ->first();

        if ($existing) {
            return $existing;
        }

        return Timecard::create([
            'employee_id' => $employee->id,
            'store_id' => $store->id,
            'status' => Timecard::STATUS_COMPLETED,
            'start_date' => $date->startOfDay(),
            'end_date' => $date->endOfDay(),
            'hours_worked' => 0,
            'created_by' => $creator->id,
        ]);
    }

    /**
     * Create a timecard with optional time details and breaks.
     */
    public function createTimecardWithDetails(
        Employee $employee,
        Store $store,
        CarbonInterface $date,
        Employee $creator,
        ?string $startTime = null,
        ?string $endTime = null,
        array $breaks = []
    ): Timecard {
        return DB::transaction(function () use ($employee, $store, $date, $creator, $startTime, $endTime, $breaks) {
            $start = $startTime ? Carbon::parse($startTime) : $date->copy()->startOfDay();
            $end = $endTime ? Carbon::parse($endTime) : null;

            $timecard = Timecard::create([
                'employee_id' => $employee->id,
                'store_id' => $store->id,
                'status' => $end ? Timecard::STATUS_COMPLETED : Timecard::STATUS_IN_PROGRESS,
                'start_date' => $start,
                'end_date' => $end,
                'hours_worked' => 0,
                'created_by' => $creator->id,
            ]);

            // Create work detail
            if ($startTime) {
                $workHours = $end ? round($start->diffInMinutes($end) / 60, 2) : null;

                TimecardDetail::create([
                    'timecard_id' => $timecard->id,
                    'type' => TimecardDetail::TYPE_WORK,
                    'start_date' => $start,
                    'end_date' => $end,
                    'hours' => $workHours,
                ]);

                // Create break details
                foreach ($breaks as $break) {
                    $breakStart = Carbon::parse($break['start_time']);
                    $breakEnd = Carbon::parse($break['end_time']);
                    $breakHours = round($breakStart->diffInMinutes($breakEnd) / 60, 2);

                    TimecardDetail::create([
                        'timecard_id' => $timecard->id,
                        'type' => TimecardDetail::TYPE_BREAK,
                        'start_date' => $breakStart,
                        'end_date' => $breakEnd,
                        'hours' => $breakHours,
                    ]);
                }

                // Recalculate total work hours (excluding breaks)
                $this->recalculateHours($timecard);
            }

            return $timecard->fresh(['details', 'store', 'employee']);
        });
    }

    /**
     * Update a timecard detail.
     */
    public function updateDetail(TimecardDetail $detail, array $data): TimecardDetail
    {
        return DB::transaction(function () use ($detail, $data) {
            // Calculate hours if start and end are provided
            if (isset($data['start_date']) && isset($data['end_date'])) {
                $start = Carbon::parse($data['start_date']);
                $end = Carbon::parse($data['end_date']);
                $data['hours'] = round($start->diffInMinutes($end) / 60, 2);
            }

            $detail->update($data);

            // Recalculate timecard hours
            $this->recalculateHours($detail->timecard);

            return $detail->fresh();
        });
    }

    /**
     * Create a timecard detail.
     */
    public function createDetail(Timecard $timecard, array $data): TimecardDetail
    {
        return DB::transaction(function () use ($timecard, $data) {
            // Calculate hours if start and end are provided
            if (isset($data['start_date']) && isset($data['end_date'])) {
                $start = Carbon::parse($data['start_date']);
                $end = Carbon::parse($data['end_date']);
                $data['hours'] = round($start->diffInMinutes($end) / 60, 2);
            }

            $detail = TimecardDetail::create([
                'timecard_id' => $timecard->id,
                ...$data,
            ]);

            // Recalculate timecard hours
            $this->recalculateHours($timecard);

            return $detail;
        });
    }

    /**
     * Delete a timecard detail.
     */
    public function deleteDetail(TimecardDetail $detail): void
    {
        DB::transaction(function () use ($detail) {
            $timecard = $detail->timecard;
            $detail->delete();
            $this->recalculateHours($timecard);
        });
    }

    /**
     * Update timecard status.
     */
    public function updateStatus(Timecard $timecard, string $status, ?Employee $updatedBy = null): Timecard
    {
        $timecard->update([
            'status' => $status,
            'updated_by' => $updatedBy?->id,
        ]);

        return $timecard->fresh();
    }

    /**
     * Delete a timecard.
     */
    public function deleteTimecard(Timecard $timecard): void
    {
        $timecard->delete();
    }

    /**
     * Expire old in-progress timecards from previous days.
     */
    public function expireOldTimecards(): int
    {
        $affected = Timecard::inProgress()
            ->whereDate('start_date', '<', today())
            ->update(['status' => Timecard::STATUS_EXPIRED]);

        return $affected;
    }

    /**
     * Get team timecards for a manager's subordinates.
     */
    public function getTeamMonthlyTimecards(Employee $manager, CarbonInterface $month): Collection
    {
        $subordinateIds = $manager->activeSubordinates()->pluck('employees.id');

        $timecards = Timecard::whereIn('employee_id', $subordinateIds)
            ->forMonth($month)
            ->with(['employee', 'store'])
            ->orderBy('start_date')
            ->get();

        return $timecards->groupBy(fn (Timecard $t) => $t->start_date->toDateString())
            ->map(function (Collection $dayTimecards, string $date) {
                $employees = $dayTimecards->groupBy('employee_id')
                    ->map(function (Collection $employeeTimecards) {
                        $employee = $employeeTimecards->first()->employee;

                        return [
                            'id' => $employee->id,
                            'name' => $employee->full_name,
                            'hours' => (float) $employeeTimecards->sum('hours_worked'),
                        ];
                    })->values()->all();

                return [
                    'date' => $date,
                    'employees' => $employees,
                    'employee_count' => count($employees),
                    'total_hours' => (float) $dayTimecards->sum('hours_worked'),
                ];
            });
    }

    /**
     * Get subordinate's timecards for a month (team leader view).
     */
    public function getSubordinateMonthlyTimecards(Employee $subordinate, CarbonInterface $month): Collection
    {
        return $this->getMonthlyTimecards($subordinate, $month);
    }

    /**
     * Get subordinate's timecards for a specific date (team leader view).
     */
    public function getSubordinateTimecardsByDate(Employee $subordinate, CarbonInterface $date): Collection
    {
        return $this->getTimecardsByDate($subordinate, $date);
    }

    /**
     * Get current timecard state for clock widget.
     *
     * @return array{timecard: Timecard|null, current_detail: TimecardDetail|null, is_on_break: bool}
     */
    public function getCurrentTimecardState(Employee $employee): array
    {
        $timecard = $this->getCurrentTimecard($employee);

        if (! $timecard) {
            return [
                'timecard' => null,
                'current_detail' => null,
                'is_on_break' => false,
            ];
        }

        $currentDetail = $timecard->getCurrentDetail();

        return [
            'timecard' => $timecard,
            'current_detail' => $currentDetail,
            'is_on_break' => $currentDetail?->type === TimecardDetail::TYPE_BREAK,
        ];
    }

    /**
     * Resolve an incomplete timecard with user-provided end time.
     */
    public function resolveIncompleteTimecard(Timecard $timecard, string $endTime, ?Employee $updatedBy = null): Timecard
    {
        $result = DB::transaction(function () use ($timecard, $endTime, $updatedBy) {
            $endDate = Carbon::parse($endTime);

            // Close all open details
            $this->closeOpenDetails($timecard, $endDate);

            // Calculate total work hours
            $totalWorkHours = $this->calculateWorkHours($timecard);

            $timecard->update([
                'status' => Timecard::STATUS_COMPLETED,
                'end_date' => $endDate,
                'user_provided_end_date' => $endDate,
                'is_inaccurate' => true,
                'hours_worked' => $totalWorkHours,
                'updated_by' => $updatedBy?->id,
            ]);

            return $timecard->fresh(['details', 'store', 'employee']);
        });

        $this->sendNotification($result, 'Incomplete Resolved');

        return $result;
    }

    protected function sendNotification(Timecard $timecard, string $action): void
    {
        $recipients = NotificationRecipient::forEventType(NotificationEventType::Timecard)
            ->where('store_id', $timecard->store_id)
            ->active()
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $timecard->loadMissing(['details', 'employee', 'store']);

        $mailable = new TimecardNotificationMail($timecard, $action);

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->queue(clone $mailable);
        }
    }
}

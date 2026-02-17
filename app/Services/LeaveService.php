<?php

namespace App\Services;

use App\Enums\LeaveRequestStatus;
use App\Models\ContractLeaveEntitlement;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaveService
{
    /**
     * Calculate total days for a leave request, accounting for half-days and weekends.
     */
    public function calculateTotalDays(string $startDate, string $endDate, string $startHalf, string $endHalf): float
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->gt($end)) {
            return 0;
        }

        // Single day
        if ($start->equalTo($end)) {
            if ($start->isWeekend()) {
                return 0;
            }

            return $startHalf === 'full' ? 1.0 : 0.5;
        }

        // Multi-day: count weekdays
        $totalDays = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if (! $current->isWeekend()) {
                $totalDays += 1.0;
            }
            $current->addDay();
        }

        // Adjust for half-days on first and last day (only if they're weekdays)
        if ($startHalf !== 'full' && ! $start->isWeekend()) {
            $totalDays -= 0.5;
        }
        if ($endHalf !== 'full' && ! $end->isWeekend()) {
            $totalDays -= 0.5;
        }

        return max(0, $totalDays);
    }

    /**
     * Store a new leave request.
     */
    public function store(array $data, User $user): LeaveRequest
    {
        $employee = $user->employee;
        $contract = $employee->activeContracts()->latest('start_date')->first();

        if (! $contract) {
            throw new \RuntimeException('No active contract found.');
        }

        $totalDays = $this->calculateTotalDays(
            $data['start_date'],
            $data['end_date'],
            $data['start_half_day'] ?? 'full',
            $data['end_half_day'] ?? 'full'
        );

        // Check overlap
        if ($this->hasOverlap($employee, $data['start_date'], $data['end_date'])) {
            throw new \RuntimeException('Leave request overlaps with an existing request.');
        }

        // Check balance for types that require it
        $leaveType = \App\Models\LeaveType::findOrFail($data['leave_type_id']);
        if ($leaveType->requires_balance) {
            $entitlement = $contract->leaveEntitlements()
                ->where('leave_type_id', $data['leave_type_id'])
                ->first();

            $remaining = $entitlement ? $entitlement->remaining_days : 0;
            if ($totalDays > $remaining) {
                throw new \RuntimeException("Insufficient leave balance. Available: {$remaining} days, Requested: {$totalDays} days.");
            }
        }

        return LeaveRequest::create([
            'employee_id' => $employee->id,
            'employee_contract_id' => $contract->id,
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_half_day' => $data['start_half_day'] ?? 'full',
            'end_half_day' => $data['end_half_day'] ?? 'full',
            'total_days' => $totalDays,
            'reason' => $data['reason'] ?? null,
            'status' => LeaveRequestStatus::PENDING,
        ]);
    }

    /**
     * Update a rejected leave request.
     */
    public function update(LeaveRequest $leaveRequest, array $data, User $user): LeaveRequest
    {
        $totalDays = $this->calculateTotalDays(
            $data['start_date'],
            $data['end_date'],
            $data['start_half_day'] ?? 'full',
            $data['end_half_day'] ?? 'full'
        );

        // Check overlap excluding current request
        if ($this->hasOverlap($leaveRequest->employee, $data['start_date'], $data['end_date'], $leaveRequest->id)) {
            throw new \RuntimeException('Leave request overlaps with an existing request.');
        }

        // Check balance for types that require it
        $leaveType = \App\Models\LeaveType::findOrFail($data['leave_type_id']);
        if ($leaveType->requires_balance) {
            $entitlement = $leaveRequest->contract->leaveEntitlements()
                ->where('leave_type_id', $data['leave_type_id'])
                ->first();

            $remaining = $entitlement ? $entitlement->remaining_days : 0;
            if ($totalDays > $remaining) {
                throw new \RuntimeException("Insufficient leave balance. Available: {$remaining} days, Requested: {$totalDays} days.");
            }
        }

        $leaveRequest->update([
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_half_day' => $data['start_half_day'] ?? 'full',
            'end_half_day' => $data['end_half_day'] ?? 'full',
            'total_days' => $totalDays,
            'reason' => $data['reason'] ?? null,
            'status' => LeaveRequestStatus::PENDING,
            'rejection_reason' => null,
        ]);

        return $leaveRequest->fresh();
    }

    /**
     * Approve a pending leave request.
     */
    public function approve(LeaveRequest $leaveRequest, User $user): LeaveRequest
    {
        if ($leaveRequest->status !== LeaveRequestStatus::PENDING) {
            throw new \RuntimeException('Only pending requests can be approved.');
        }

        return DB::transaction(function () use ($leaveRequest, $user) {
            $leaveRequest->update([
                'status' => LeaveRequestStatus::APPROVED,
                'resolved_at' => now(),
                'resolved_by' => $user->id,
            ]);

            // Increment taken_days on the entitlement
            $leaveType = $leaveRequest->leaveType;
            if ($leaveType->requires_balance) {
                $entitlement = ContractLeaveEntitlement::firstOrCreate(
                    [
                        'employee_contract_id' => $leaveRequest->employee_contract_id,
                        'leave_type_id' => $leaveRequest->leave_type_id,
                    ],
                    ['entitled_days' => 0, 'taken_days' => 0]
                );

                $entitlement->increment('taken_days', (float) $leaveRequest->total_days);
            }

            return $leaveRequest->fresh();
        });
    }

    /**
     * Reject a pending leave request.
     */
    public function reject(LeaveRequest $leaveRequest, string $reason, User $user): LeaveRequest
    {
        if ($leaveRequest->status !== LeaveRequestStatus::PENDING) {
            throw new \RuntimeException('Only pending requests can be rejected.');
        }

        $leaveRequest->update([
            'status' => LeaveRequestStatus::REJECTED,
            'rejection_reason' => $reason,
            'resolved_at' => now(),
            'resolved_by' => $user->id,
        ]);

        return $leaveRequest->fresh();
    }

    /**
     * Cancel a pending or approved leave request.
     */
    public function cancel(LeaveRequest $leaveRequest, User $user): LeaveRequest
    {
        if (! in_array($leaveRequest->status, [LeaveRequestStatus::PENDING, LeaveRequestStatus::APPROVED])) {
            throw new \RuntimeException('Only pending or approved requests can be cancelled.');
        }

        return DB::transaction(function () use ($leaveRequest, $user) {
            $wasApproved = $leaveRequest->status === LeaveRequestStatus::APPROVED;

            $leaveRequest->update([
                'status' => LeaveRequestStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
            ]);

            // Decrement taken_days if was approved
            if ($wasApproved) {
                $leaveType = $leaveRequest->leaveType;
                if ($leaveType->requires_balance) {
                    $entitlement = ContractLeaveEntitlement::where('employee_contract_id', $leaveRequest->employee_contract_id)
                        ->where('leave_type_id', $leaveRequest->leave_type_id)
                        ->first();

                    if ($entitlement) {
                        $entitlement->decrement('taken_days', (float) $leaveRequest->total_days);
                        // Ensure taken_days doesn't go below 0
                        if ($entitlement->taken_days < 0) {
                            $entitlement->update(['taken_days' => 0]);
                        }
                    }
                }
            }

            return $leaveRequest->fresh();
        });
    }

    /**
     * Get leave balances for a contract.
     */
    public function getLeaveBalances(EmployeeContract $contract): Collection
    {
        $leaveTypes = \App\Models\LeaveType::active()->orderBy('sort_order')->get();

        return $leaveTypes->map(function ($leaveType) use ($contract) {
            $entitlement = $contract->leaveEntitlements
                ->where('leave_type_id', $leaveType->id)
                ->first();

            return [
                'leave_type' => $leaveType,
                'entitled_days' => $entitlement ? (float) $entitlement->entitled_days : 0,
                'taken_days' => $entitlement ? (float) $entitlement->taken_days : 0,
                'remaining_days' => $entitlement ? $entitlement->remaining_days : 0,
            ];
        });
    }

    /**
     * Get leave data for calendar display.
     */
    public function getLeaveForCalendar(Employee $employee, CarbonInterface $month): Collection
    {
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        return LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', [LeaveRequestStatus::PENDING, LeaveRequestStatus::APPROVED])
            ->where('start_date', '<=', $endOfMonth)
            ->where('end_date', '>=', $startOfMonth)
            ->with('leaveType')
            ->get();
    }

    /**
     * Get leave data keyed by date for calendar display.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getLeaveCalendarData(Employee $employee, CarbonInterface $month): array
    {
        $leaveRequests = $this->getLeaveForCalendar($employee, $month);
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $data = [];

        foreach ($leaveRequests as $request) {
            $start = Carbon::parse($request->start_date)->max($startOfMonth);
            $end = Carbon::parse($request->end_date)->min($endOfMonth);
            $current = $start->copy();

            while ($current->lte($end)) {
                $dateString = $current->toDateString();
                $isHalfDay = false;
                $halfDayType = null;

                if ($request->start_date->equalTo($request->end_date)) {
                    // Single day
                    $halfValue = $request->start_half_day instanceof \App\Enums\HalfDayType
                        ? $request->start_half_day->value
                        : $request->start_half_day;
                    if ($halfValue !== 'full') {
                        $isHalfDay = true;
                        $halfDayType = $halfValue;
                    }
                } elseif ($current->equalTo($request->start_date)) {
                    $halfValue = $request->start_half_day instanceof \App\Enums\HalfDayType
                        ? $request->start_half_day->value
                        : $request->start_half_day;
                    if ($halfValue !== 'full') {
                        $isHalfDay = true;
                        $halfDayType = $halfValue;
                    }
                } elseif ($current->equalTo($request->end_date)) {
                    $halfValue = $request->end_half_day instanceof \App\Enums\HalfDayType
                        ? $request->end_half_day->value
                        : $request->end_half_day;
                    if ($halfValue !== 'full') {
                        $isHalfDay = true;
                        $halfDayType = $halfValue;
                    }
                }

                $statusValue = $request->status instanceof LeaveRequestStatus
                    ? $request->status->value
                    : $request->status;

                $data[$dateString][] = [
                    'id' => $request->id,
                    'leave_type' => $request->leaveType->name,
                    'color' => $request->leaveType->color ?? '#9E9E9E',
                    'status' => $statusValue,
                    'is_half_day' => $isHalfDay,
                    'half_day_type' => $halfDayType,
                ];

                $current->addDay();
            }
        }

        return $data;
    }

    /**
     * Check if a leave request overlaps with existing non-cancelled requests.
     */
    public function hasOverlap(Employee $employee, string $startDate, string $endDate, ?int $excludeId = null): bool
    {
        $query = LeaveRequest::where('employee_id', $employee->id)
            ->whereNotIn('status', [LeaveRequestStatus::CANCELLED, LeaveRequestStatus::REJECTED])
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if a user can approve a leave request.
     */
    public function canApprove(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user is a manager of the employee
        if ($user->employee) {
            return $user->employee->activeSubordinates()
                ->where('employees.id', $leaveRequest->employee_id)
                ->exists();
        }

        return false;
    }

    /**
     * Get pending leave requests from subordinates.
     */
    public function getPendingForManager(Employee $manager): Collection
    {
        $subordinateIds = $manager->activeSubordinates()->pluck('employees.id');

        return LeaveRequest::whereIn('employee_id', $subordinateIds)
            ->pending()
            ->with(['employee', 'leaveType'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

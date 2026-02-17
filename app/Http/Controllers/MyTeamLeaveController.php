<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MyTeamLeaveController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee || ! $employee->hasSubordinates()) {
            abort(403, 'You do not have access to this page.');
        }

        $subordinateIds = $this->getAllSubordinateIds($employee);

        $query = LeaveRequest::whereIn('employee_id', $subordinateIds)
            ->with(['employee', 'leaveType'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Employee filter
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($eq) use ($search) {
                    $eq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })->orWhereHas('leaveType', function ($lq) use ($search) {
                    $lq->where('name', 'like', "%{$search}%");
                });
            });
        }

        $leaveRequests = $query->paginate(15)->withQueryString();

        // Get subordinates for filter dropdown
        $subordinates = $this->getAllSubordinates($employee);

        return Inertia::render('MyTeamLeave/Index', [
            'leaveRequests' => [
                'data' => LeaveRequestResource::collection($leaveRequests->items())->resolve(),
                'current_page' => $leaveRequests->currentPage(),
                'last_page' => $leaveRequests->lastPage(),
                'per_page' => $leaveRequests->perPage(),
                'total' => $leaveRequests->total(),
            ],
            'subordinates' => $subordinates,
            'filters' => [
                'status' => $request->input('status', ''),
                'employee_id' => $request->input('employee_id', ''),
                'search' => $request->input('search', ''),
            ],
            'breadcrumbs' => [
                ['title' => 'My Tools'],
                ['title' => 'Team Leave'],
            ],
        ]);
    }

    public function show(Request $request, LeaveRequest $leaveRequest): InertiaResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee || ! $employee->hasSubordinates()) {
            abort(403);
        }

        // Verify the leave request belongs to a subordinate
        $subordinateIds = $this->getAllSubordinateIds($employee);
        if (! in_array($leaveRequest->employee_id, $subordinateIds)) {
            abort(403, 'You can only view leave requests from your team members.');
        }

        $leaveRequest->load(['employee', 'leaveType', 'resolvedByUser', 'cancelledByUser']);

        return Inertia::render('MyTeamLeave/Show', [
            'leaveRequest' => (new LeaveRequestResource($leaveRequest))->resolve(),
            'canApprove' => $this->leaveService->canApprove($user, $leaveRequest),
            'breadcrumbs' => [
                ['title' => 'My Tools'],
                ['title' => 'Team Leave', 'href' => '/my-team-leave'],
                ['title' => 'View'],
            ],
        ]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $user = $request->user();

        if (! $this->leaveService->canApprove($user, $leaveRequest)) {
            abort(403, 'You do not have permission to approve this request.');
        }

        try {
            $this->leaveService->approve($leaveRequest, $user);

            return redirect()->route('my-team-leave.show', $leaveRequest)
                ->with('success', 'Leave request approved.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }

    public function reject(RejectLeaveRequestRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $user = $request->user();

        if (! $this->leaveService->canApprove($user, $leaveRequest)) {
            abort(403, 'You do not have permission to reject this request.');
        }

        try {
            $this->leaveService->reject($leaveRequest, $request->validated('rejection_reason'), $user);

            return redirect()->route('my-team-leave.show', $leaveRequest)
                ->with('success', 'Leave request rejected.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAllSubordinates(Employee $manager): array
    {
        $subordinates = [];
        $this->collectSubordinates($manager, $subordinates);

        return $subordinates;
    }

    private function collectSubordinates(Employee $employee, array &$subordinates): void
    {
        foreach ($employee->activeSubordinates as $subordinate) {
            $subordinates[] = [
                'id' => $subordinate->id,
                'name' => $subordinate->full_name,
                'employee_number' => $subordinate->employee_number,
                'profile_picture_url' => $subordinate->getProfilePictureUrl(),
            ];
            $this->collectSubordinates($subordinate, $subordinates);
        }
    }

    /**
     * @return array<int>
     */
    private function getAllSubordinateIds(Employee $manager): array
    {
        $ids = [];
        foreach ($manager->activeSubordinates as $subordinate) {
            $ids[] = $subordinate->id;
            $ids = array_merge($ids, $this->getAllSubordinateIds($subordinate));
        }

        return $ids;
    }
}

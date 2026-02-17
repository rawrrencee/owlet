<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequestRequest;
use App\Http\Requests\UpdateLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Http\Resources\LeaveTypeResource;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LeaveController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        // Get leave balances from active contract
        $balances = [];
        $activeContract = $employee?->activeContracts()
            ->with('leaveEntitlements.leaveType')
            ->latest('start_date')
            ->first();

        if ($activeContract) {
            $balances = $this->leaveService->getLeaveBalances($activeContract);
        }

        // Get paginated leave requests for this employee
        $query = LeaveRequest::forEmployee($employee?->id ?? 0)
            ->with(['leaveType'])
            ->orderByDesc('created_at');

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search by leave type name
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('leaveType', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $leaveRequests = $query->paginate(15)->withQueryString();

        return Inertia::render('Leave/Index', [
            'balances' => $balances,
            'leaveRequests' => [
                'data' => LeaveRequestResource::collection($leaveRequests->items())->resolve(),
                'current_page' => $leaveRequests->currentPage(),
                'last_page' => $leaveRequests->lastPage(),
                'per_page' => $leaveRequests->perPage(),
                'total' => $leaveRequests->total(),
            ],
            'filters' => [
                'status' => $request->input('status', ''),
                'search' => $request->input('search', ''),
            ],
            'breadcrumbs' => [
                ['title' => 'Platform'],
                ['title' => 'My Leave'],
            ],
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $leaveTypes = LeaveType::active()->orderBy('sort_order')->get();

        return Inertia::render('Leave/Create', [
            'leaveTypes' => LeaveTypeResource::collection($leaveTypes)->resolve(),
            'breadcrumbs' => [
                ['title' => 'Platform'],
                ['title' => 'My Leave', 'href' => '/leave'],
                ['title' => 'Apply'],
            ],
        ]);
    }

    public function store(StoreLeaveRequestRequest $request): RedirectResponse
    {
        try {
            $this->leaveService->store($request->validated(), $request->user());

            return redirect()->route('leave.index')
                ->with('success', 'Leave request submitted successfully.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }

    public function show(Request $request, LeaveRequest $leaveRequest): InertiaResponse
    {
        // Ensure user can only view their own requests
        $this->authorizeOwnRequest($request, $leaveRequest);

        $leaveRequest->load(['leaveType', 'employee', 'resolvedByUser', 'cancelledByUser']);

        return Inertia::render('Leave/View', [
            'leaveRequest' => (new LeaveRequestResource($leaveRequest))->resolve(),
            'breadcrumbs' => [
                ['title' => 'Platform'],
                ['title' => 'My Leave', 'href' => '/leave'],
                ['title' => 'View'],
            ],
        ]);
    }

    public function edit(Request $request, LeaveRequest $leaveRequest): InertiaResponse
    {
        $this->authorizeOwnRequest($request, $leaveRequest);

        // Only rejected requests can be edited
        if ($leaveRequest->status->value !== 'rejected') {
            abort(403, 'Only rejected requests can be edited.');
        }

        $leaveRequest->load(['leaveType']);
        $leaveTypes = LeaveType::active()->orderBy('sort_order')->get();

        return Inertia::render('Leave/Edit', [
            'leaveRequest' => (new LeaveRequestResource($leaveRequest))->resolve(),
            'leaveTypes' => LeaveTypeResource::collection($leaveTypes)->resolve(),
            'breadcrumbs' => [
                ['title' => 'Platform'],
                ['title' => 'My Leave', 'href' => '/leave'],
                ['title' => 'Edit & Resubmit'],
            ],
        ]);
    }

    public function update(UpdateLeaveRequestRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorizeOwnRequest($request, $leaveRequest);

        if ($leaveRequest->status->value !== 'rejected') {
            abort(403, 'Only rejected requests can be edited.');
        }

        try {
            $this->leaveService->update($leaveRequest, $request->validated(), $request->user());

            return redirect()->route('leave.index')
                ->with('success', 'Leave request updated and resubmitted.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorizeOwnRequest($request, $leaveRequest);

        try {
            $this->leaveService->cancel($leaveRequest, $request->user());

            return redirect()->route('leave.show', $leaveRequest)
                ->with('success', 'Leave request cancelled.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }

    protected function authorizeOwnRequest(Request $request, LeaveRequest $leaveRequest): void
    {
        $employee = $request->user()->employee;

        if (! $employee || $leaveRequest->employee_id !== $employee->id) {
            abort(403, 'You can only access your own leave requests.');
        }
    }
}

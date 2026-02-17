<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LeaveManagementController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $query = LeaveRequest::with(['employee', 'leaveType'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search by employee name or leave type
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

        return Inertia::render('Management/Leave/Index', [
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
                ['title' => 'Organisation'],
                ['title' => 'Leave Requests'],
            ],
        ]);
    }

    public function show(Request $request, LeaveRequest $leaveRequest): InertiaResponse
    {
        $leaveRequest->load(['employee', 'leaveType', 'resolvedByUser', 'cancelledByUser']);

        return Inertia::render('Management/Leave/Show', [
            'leaveRequest' => (new LeaveRequestResource($leaveRequest))->resolve(),
            'breadcrumbs' => [
                ['title' => 'Organisation'],
                ['title' => 'Leave Requests', 'href' => '/management/leave'],
                ['title' => 'View'],
            ],
        ]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        try {
            $this->leaveService->approve($leaveRequest, $request->user());

            return redirect()->route('management.leave.show', $leaveRequest)
                ->with('success', 'Leave request approved.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }

    public function reject(RejectLeaveRequestRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        try {
            $this->leaveService->reject($leaveRequest, $request->validated('rejection_reason'), $request->user());

            return redirect()->route('management.leave.show', $leaveRequest)
                ->with('success', 'Leave request rejected.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }
}

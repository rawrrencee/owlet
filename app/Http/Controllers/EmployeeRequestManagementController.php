<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeRequestStatus;
use App\Http\Requests\RejectEmployeeRequestRequest;
use App\Http\Resources\EmployeeRequestResource;
use App\Models\Country;
use App\Models\EmployeeRequest;
use App\Models\SiteSetting;
use App\Services\EmployeeRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EmployeeRequestManagementController extends Controller
{
    public function __construct(
        private readonly EmployeeRequestService $service
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $query = EmployeeRequest::query()
            ->with(['country', 'nationalityCountry', 'approvedByUser', 'rejectedByUser']);

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $requests = $query->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Management/EmployeeRequests/Index', [
            'requests' => EmployeeRequestResource::collection($requests),
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
            ],
            'statuses' => collect(EmployeeRequestStatus::cases())->map(fn ($s) => [
                'label' => $s->label(),
                'value' => $s->value,
            ]),
        ]);
    }

    public function show(EmployeeRequest $employeeRequest): InertiaResponse
    {
        $employeeRequest->load(['country', 'nationalityCountry', 'approvedByUser', 'rejectedByUser']);

        return Inertia::render('Management/EmployeeRequests/Show', [
            'employeeRequest' => new EmployeeRequestResource($employeeRequest),
        ]);
    }

    public function approve(EmployeeRequest $employeeRequest, Request $request): \Illuminate\Http\RedirectResponse
    {
        if ($employeeRequest->status !== EmployeeRequestStatus::PENDING) {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        $this->service->approve($employeeRequest, $request->user());

        return back()->with('success', 'Application approved. An invitation has been sent to the applicant.');
    }

    public function reject(EmployeeRequest $employeeRequest, RejectEmployeeRequestRequest $request): \Illuminate\Http\RedirectResponse
    {
        if ($employeeRequest->status !== EmployeeRequestStatus::PENDING) {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $this->service->reject($employeeRequest, $request->user(), $request->validated('rejection_reason'));

        return back()->with('success', 'Application rejected.');
    }

    public function settings(): InertiaResponse
    {
        return Inertia::render('Management/EmployeeRequests/Settings', [
            'signupEnabled' => SiteSetting::get('signup_enabled', '0') === '1',
            'hasAccessCode' => (bool) SiteSetting::get('signup_access_code'),
        ]);
    }

    public function updateSettings(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'signup_enabled' => ['required', 'boolean'],
            'access_code' => ['nullable', 'string', 'min:4', 'max:50'],
            'clear_access_code' => ['nullable', 'boolean'],
        ]);

        SiteSetting::set('signup_enabled', $request->boolean('signup_enabled') ? '1' : '0');

        if ($request->boolean('clear_access_code')) {
            SiteSetting::set('signup_access_code', null);
        } elseif ($request->filled('access_code')) {
            SiteSetting::set('signup_access_code', Hash::make($request->input('access_code')));
        }

        return back()->with('success', 'Settings updated.');
    }
}

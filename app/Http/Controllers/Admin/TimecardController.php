<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTimecardDetailRequest;
use App\Http\Requests\StoreTimecardRequest;
use App\Http\Requests\UpdateTimecardDetailRequest;
use App\Http\Requests\UpdateTimecardRequest;
use App\Http\Resources\TimecardResource;
use App\Models\Employee;
use App\Models\Store;
use App\Models\Timecard;
use App\Models\TimecardDetail;
use App\Services\TimecardService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TimecardController extends Controller
{
    public function __construct(
        private readonly TimecardService $timecardService
    ) {}

    /**
     * Display admin timecards index with calendar and search.
     */
    public function index(Request $request): InertiaResponse
    {
        $monthParam = $request->query('month');
        $month = $monthParam ? Carbon::parse($monthParam)->startOfMonth() : now()->startOfMonth();

        // Get admin monthly overview for calendar
        $monthlyData = $this->timecardService->getAdminMonthlyOverview($month);

        // Get employees for search dropdown
        $employees = Employee::whereNull('termination_date')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->full_name,
                'employee_number' => $e->employee_number,
            ]);

        return Inertia::render('Management/Timecards/Index', [
            'month' => $month->toDateString(),
            'monthlyData' => $monthlyData,
            'employees' => $employees,
        ]);
    }

    /**
     * Display all timecards for a specific date.
     */
    public function byDate(Request $request, string $date): InertiaResponse
    {
        $dateCarbon = Carbon::parse($date);
        $timecards = $this->timecardService->getAdminTimecardsByDate($dateCarbon);

        // Group by employee
        $groupedTimecards = $timecards->groupBy('employee_id')
            ->map(function ($employeeTimecards) {
                $employee = $employeeTimecards->first()->employee;

                return [
                    'employee' => [
                        'id' => $employee->id,
                        'name' => $employee->full_name,
                        'employee_number' => $employee->employee_number,
                        'profile_picture_url' => $employee->getProfilePictureUrl(),
                    ],
                    'timecards' => TimecardResource::collection($employeeTimecards),
                    'total_hours' => (float) $employeeTimecards->sum('hours_worked'),
                ];
            })
            ->values();

        return Inertia::render('Management/Timecards/ByDate', [
            'date' => $dateCarbon->toDateString(),
            'dateFormatted' => $dateCarbon->format('l, F j, Y'),
            'groupedTimecards' => $groupedTimecards,
            'totalHours' => (float) $timecards->sum('hours_worked'),
            'employeeCount' => $timecards->pluck('employee_id')->unique()->count(),
        ]);
    }

    /**
     * Display timecards for a specific employee.
     */
    public function byEmployee(Request $request, Employee $employee): InertiaResponse
    {
        $monthParam = $request->query('month');
        $month = $monthParam ? Carbon::parse($monthParam)->startOfMonth() : now()->startOfMonth();

        // Get employee's monthly timecards
        $monthlyData = $this->timecardService->getMonthlyTimecards($employee, $month);

        return Inertia::render('Management/Timecards/ByEmployee', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'employee_number' => $employee->employee_number,
                'profile_picture_url' => $employee->getProfilePictureUrl(),
            ],
            'month' => $month->toDateString(),
            'monthlyData' => $monthlyData,
        ]);
    }

    /**
     * Show create form.
     */
    public function create(Request $request): InertiaResponse
    {
        $employees = Employee::whereNull('termination_date')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->full_name,
                'employee_number' => $e->employee_number,
            ]);

        $stores = Store::where('active', true)
            ->orderBy('store_name')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->store_name,
                'code' => $s->store_code,
            ]);

        $preselectedEmployee = null;
        $preselectedDate = null;

        if ($request->has('employee_id')) {
            $employee = Employee::find($request->employee_id);
            if ($employee) {
                $preselectedEmployee = [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'employee_number' => $employee->employee_number,
                ];
            }
        }

        if ($request->has('date')) {
            $preselectedDate = $request->date;
        }

        return Inertia::render('Management/Timecards/Create', [
            'employees' => $employees,
            'stores' => $stores,
            'preselectedEmployee' => $preselectedEmployee,
            'preselectedDate' => $preselectedDate,
            'statuses' => [
                ['value' => Timecard::STATUS_IN_PROGRESS, 'label' => 'In Progress'],
                ['value' => Timecard::STATUS_COMPLETED, 'label' => 'Completed'],
                ['value' => Timecard::STATUS_EXPIRED, 'label' => 'Expired'],
            ],
        ]);
    }

    /**
     * Store a new timecard.
     */
    public function store(StoreTimecardRequest $request): RedirectResponse
    {
        $user = $request->user();
        $employee = Employee::findOrFail($request->employee_id);
        $store = Store::findOrFail($request->store_id);
        $date = Carbon::parse($request->date);

        $timecard = $this->timecardService->getOrCreateTimecard(
            $employee,
            $store,
            $date,
            $user->employee
        );

        return redirect()->route('management.timecards.edit', $timecard)
            ->with('success', 'Timecard created. Please add time entries.');
    }

    /**
     * Display a timecard.
     */
    public function show(Timecard $timecard): InertiaResponse
    {
        $timecard->load(['employee', 'store', 'details', 'createdByEmployee', 'updatedByEmployee']);

        return Inertia::render('Management/Timecards/Show', [
            'timecard' => new TimecardResource($timecard),
        ]);
    }

    /**
     * Show edit form.
     */
    public function edit(Timecard $timecard): InertiaResponse
    {
        $timecard->load(['employee', 'store', 'details', 'createdByEmployee', 'updatedByEmployee']);

        $stores = Store::where('active', true)
            ->orderBy('store_name')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->store_name,
                'code' => $s->store_code,
            ]);

        return Inertia::render('Management/Timecards/Edit', [
            'timecard' => new TimecardResource($timecard),
            'stores' => $stores,
            'statuses' => [
                ['value' => Timecard::STATUS_IN_PROGRESS, 'label' => 'In Progress'],
                ['value' => Timecard::STATUS_COMPLETED, 'label' => 'Completed'],
                ['value' => Timecard::STATUS_EXPIRED, 'label' => 'Expired'],
            ],
            'detailTypes' => [
                ['value' => TimecardDetail::TYPE_WORK, 'label' => 'Work'],
                ['value' => TimecardDetail::TYPE_BREAK, 'label' => 'Break'],
            ],
        ]);
    }

    /**
     * Update a timecard.
     */
    public function update(UpdateTimecardRequest $request, Timecard $timecard): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validated();
        $data['updated_by'] = $user->employee?->id;

        $timecard->update($data);

        // Recalculate hours if dates changed
        if ($request->has('start_date') || $request->has('end_date')) {
            $this->timecardService->recalculateHours($timecard);
        }

        return back()->with('success', 'Timecard updated.');
    }

    /**
     * Delete a timecard.
     */
    public function destroy(Timecard $timecard): RedirectResponse
    {
        $employeeId = $timecard->employee_id;
        $date = $timecard->start_date->toDateString();

        $this->timecardService->deleteTimecard($timecard);

        return redirect()->route('management.timecards.index')
            ->with('success', 'Timecard deleted.');
    }

    /**
     * Add a detail to a timecard.
     */
    public function storeDetail(StoreTimecardDetailRequest $request, Timecard $timecard): RedirectResponse
    {
        $this->timecardService->createDetail($timecard, $request->validated());

        return back()->with('success', 'Time entry added.');
    }

    /**
     * Update a timecard detail.
     */
    public function updateDetail(
        UpdateTimecardDetailRequest $request,
        Timecard $timecard,
        TimecardDetail $detail
    ): RedirectResponse {
        if ($detail->timecard_id !== $timecard->id) {
            abort(404);
        }

        $this->timecardService->updateDetail($detail, $request->validated());

        return back()->with('success', 'Time entry updated.');
    }

    /**
     * Delete a timecard detail.
     */
    public function destroyDetail(Timecard $timecard, TimecardDetail $detail): RedirectResponse
    {
        if ($detail->timecard_id !== $timecard->id) {
            abort(404);
        }

        $this->timecardService->deleteDetail($detail);

        return back()->with('success', 'Time entry deleted.');
    }
}

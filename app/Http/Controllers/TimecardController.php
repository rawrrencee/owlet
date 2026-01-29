<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClockInRequest;
use App\Http\Resources\TimecardResource;
use App\Models\Store;
use App\Models\Timecard;
use App\Services\TimecardService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
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
     * Display My Timecards calendar page.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            abort(403, 'You must have an employee record to view timecards.');
        }

        $monthParam = $request->query('month');
        $month = $monthParam ? Carbon::parse($monthParam)->startOfMonth() : now()->startOfMonth();

        // Get monthly timecard data for calendar
        $monthlyData = $this->timecardService->getMonthlyTimecards($employee, $month);

        // Get monthly stats
        $monthlyStats = $this->timecardService->getMonthlyStats($employee, $month);

        // Get current timecard state for clock widget
        $currentState = $this->timecardService->getCurrentTimecardState($employee);

        // Get employee's assigned stores for clock in dropdown
        $stores = $employee->activeStores()
            ->select('stores.id', 'stores.store_name', 'stores.store_code')
            ->get()
            ->map(fn ($store) => [
                'id' => $store->id,
                'name' => $store->store_name,
                'code' => $store->store_code,
            ]);

        return Inertia::render('Timecards/Index', [
            'month' => $month->toDateString(),
            'monthlyData' => $monthlyData,
            'monthlyStats' => $monthlyStats,
            'currentTimecard' => $currentState['timecard']
                ? (new TimecardResource($currentState['timecard']))->resolve()
                : null,
            'isOnBreak' => $currentState['is_on_break'],
            'stores' => $stores,
        ]);
    }

    /**
     * Display timecards for a specific date.
     */
    public function show(Request $request, string $date): InertiaResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            abort(403, 'You must have an employee record to view timecards.');
        }

        $dateCarbon = Carbon::parse($date);
        $timecards = $this->timecardService->getTimecardsByDate($employee, $dateCarbon);

        return Inertia::render('Timecards/Show', [
            'date' => $dateCarbon->toDateString(),
            'dateFormatted' => $dateCarbon->format('l, F j, Y'),
            'timecards' => TimecardResource::collection($timecards),
        ]);
    }

    /**
     * Get current timecard state for clock widget (JSON response).
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'timecard' => null,
                'is_on_break' => false,
                'stores' => [],
            ]);
        }

        $currentState = $this->timecardService->getCurrentTimecardState($employee);

        // Get employee's assigned stores for clock in dropdown
        $stores = $employee->activeStores()
            ->select('stores.id', 'stores.store_name', 'stores.store_code')
            ->get()
            ->map(fn ($store) => [
                'id' => $store->id,
                'name' => $store->store_name,
                'code' => $store->store_code,
            ]);

        return response()->json([
            'timecard' => $currentState['timecard']
                ? new TimecardResource($currentState['timecard'])
                : null,
            'is_on_break' => $currentState['is_on_break'],
            'stores' => $stores,
        ]);
    }

    /**
     * Clock in at a store.
     */
    public function clockIn(ClockInRequest $request): RedirectResponse
    {
        $user = $request->user();
        $employee = $user->employee;
        $store = Store::findOrFail($request->store_id);

        $this->timecardService->clockIn($employee, $store, $employee);

        return back()->with('success', "Clocked in at {$store->store_name}.");
    }

    /**
     * Clock out from a timecard.
     */
    public function clockOut(Request $request, Timecard $timecard): RedirectResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        // Verify the timecard belongs to this employee
        if ($timecard->employee_id !== $employee->id) {
            abort(403, 'You can only clock out from your own timecard.');
        }

        if (! $timecard->is_in_progress) {
            return back()->with('error', 'This timecard is not in progress.');
        }

        $this->timecardService->clockOut($timecard, $employee);

        return back()->with('success', 'Clocked out successfully.');
    }

    /**
     * Start a break.
     */
    public function startBreak(Request $request, Timecard $timecard): RedirectResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        // Verify the timecard belongs to this employee
        if ($timecard->employee_id !== $employee->id) {
            abort(403, 'You can only start a break on your own timecard.');
        }

        if (! $timecard->is_in_progress) {
            return back()->with('error', 'This timecard is not in progress.');
        }

        if ($timecard->isOnBreak()) {
            return back()->with('error', 'You are already on break.');
        }

        $this->timecardService->startBreak($timecard, $employee);

        return back()->with('success', 'Break started.');
    }

    /**
     * End a break.
     */
    public function endBreak(Request $request, Timecard $timecard): RedirectResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        // Verify the timecard belongs to this employee
        if ($timecard->employee_id !== $employee->id) {
            abort(403, 'You can only end a break on your own timecard.');
        }

        if (! $timecard->is_in_progress) {
            return back()->with('error', 'This timecard is not in progress.');
        }

        if (! $timecard->isOnBreak()) {
            return back()->with('error', 'You are not currently on break.');
        }

        $this->timecardService->endBreak($timecard, $employee);

        return back()->with('success', 'Break ended. Back to work!');
    }

    /**
     * Resolve an incomplete timecard with user-provided end time.
     */
    public function resolveIncomplete(Request $request, Timecard $timecard): RedirectResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        // Verify the timecard belongs to this employee
        if ($timecard->employee_id !== $employee->id) {
            abort(403, 'You can only resolve your own timecards.');
        }

        if (! $timecard->is_incomplete) {
            return back()->with('error', 'This timecard is not marked as incomplete.');
        }

        if ($timecard->user_provided_end_date) {
            return back()->with('error', 'This timecard has already been resolved.');
        }

        $validated = $request->validate([
            'end_time' => ['required', 'date', 'after:' . $timecard->start_date->toIso8601String()],
        ]);

        $this->timecardService->resolveIncompleteTimecard($timecard, $validated['end_time'], $employee);

        return back()->with('success', 'Timecard resolved successfully.');
    }
}

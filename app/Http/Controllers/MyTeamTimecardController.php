<?php

namespace App\Http\Controllers;

use App\Http\Resources\TimecardResource;
use App\Models\Employee;
use App\Services\TimecardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MyTeamTimecardController extends Controller
{
    public function __construct(
        private readonly TimecardService $timecardService
    ) {}

    /**
     * Display team timecards calendar (aggregated view of all subordinates).
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee || ! $employee->hasSubordinates()) {
            abort(403, 'You do not have access to this page.');
        }

        $monthParam = $request->query('month');
        $month = $monthParam ? Carbon::parse($monthParam)->startOfMonth() : now()->startOfMonth();

        // Get team timecards data for calendar
        $monthlyData = $this->timecardService->getTeamMonthlyTimecards($employee, $month);

        // Get all subordinates for the filter/list
        $subordinates = $this->getAllSubordinates($employee);

        return Inertia::render('MyTeamTimecards/Index', [
            'month' => $month->toDateString(),
            'monthlyData' => $monthlyData,
            'subordinates' => $subordinates,
        ]);
    }

    /**
     * Display a subordinate's monthly calendar.
     */
    public function show(Request $request, Employee $employee): InertiaResponse
    {
        $user = $request->user();
        $manager = $user->employee;

        if (! $manager || ! $manager->hasSubordinates()) {
            abort(403, 'You do not have access to this page.');
        }

        // Verify that the requested employee is a subordinate
        if (! $this->isSubordinate($manager, $employee)) {
            abort(403, 'You do not have access to view this employee\'s timecards.');
        }

        $monthParam = $request->query('month');
        $month = $monthParam ? Carbon::parse($monthParam)->startOfMonth() : now()->startOfMonth();

        // Get subordinate's monthly timecard data
        $monthlyData = $this->timecardService->getSubordinateMonthlyTimecards($employee, $month);

        return Inertia::render('MyTeamTimecards/Show', [
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
     * Display a subordinate's timecards for a specific date.
     */
    public function showDate(Request $request, Employee $employee, string $date): InertiaResponse
    {
        $user = $request->user();
        $manager = $user->employee;

        if (! $manager || ! $manager->hasSubordinates()) {
            abort(403, 'You do not have access to this page.');
        }

        // Verify that the requested employee is a subordinate
        if (! $this->isSubordinate($manager, $employee)) {
            abort(403, 'You do not have access to view this employee\'s timecards.');
        }

        $dateCarbon = Carbon::parse($date);
        $timecards = $this->timecardService->getSubordinateTimecardsByDate($employee, $dateCarbon);

        return Inertia::render('MyTeamTimecards/ShowDate', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'employee_number' => $employee->employee_number,
                'profile_picture_url' => $employee->getProfilePictureUrl(),
            ],
            'date' => $dateCarbon->toDateString(),
            'dateFormatted' => $dateCarbon->format('l, F j, Y'),
            'timecards' => TimecardResource::collection($timecards),
        ]);
    }

    /**
     * Get all subordinates recursively for a manager.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAllSubordinates(Employee $manager): array
    {
        $subordinates = [];
        $this->collectSubordinates($manager, $subordinates);

        return $subordinates;
    }

    /**
     * Collect subordinates recursively.
     *
     * @param  array<int, array<string, mixed>>  $subordinates
     */
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
     * Check if an employee is a subordinate (direct or nested) of a manager.
     */
    private function isSubordinate(Employee $manager, Employee $employee): bool
    {
        $subordinateIds = $this->getAllSubordinateIds($manager);

        return in_array($employee->id, $subordinateIds);
    }

    /**
     * Get all subordinate IDs recursively for a manager.
     *
     * @return array<int>
     */
    private function getAllSubordinateIds(Employee $manager): array
    {
        $ids = [];
        $directSubordinates = $manager->activeSubordinates;

        foreach ($directSubordinates as $subordinate) {
            $ids[] = $subordinate->id;
            $ids = array_merge($ids, $this->getAllSubordinateIds($subordinate));
        }

        return $ids;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\HierarchyVisibilitySetting;
use App\Services\HierarchyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MyTeamController extends Controller
{
    public function __construct(
        private readonly HierarchyService $hierarchyService
    ) {}

    /**
     * Display the My Team page for the current user.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee || ! $employee->hasSubordinates()) {
            abort(403, 'You do not have access to this page.');
        }

        // Get subordinate tree with visibility-filtered data
        $subordinates = $this->hierarchyService->getSubordinateTreeForManager($employee);

        // Get visibility settings for display
        $visibilitySettings = $employee->hierarchyVisibility;
        $visibleSections = $visibilitySettings?->visible_sections ?? [];

        return Inertia::render('MyTeam/Index', [
            'subordinates' => $subordinates,
            'visibleSections' => $visibleSections,
            'availableSections' => HierarchyVisibilitySetting::getAvailableSections(),
        ]);
    }

    /**
     * Display a subordinate's details.
     */
    public function show(Request $request, Employee $employee): InertiaResponse
    {
        $user = $request->user();
        $manager = $user->employee;

        if (! $manager || ! $manager->hasSubordinates()) {
            abort(403, 'You do not have access to this page.');
        }

        // Verify that the requested employee is a subordinate (direct or nested)
        if (! $this->isSubordinate($manager, $employee)) {
            abort(403, 'You do not have access to view this employee.');
        }

        // Get visibility settings
        $visibilitySettings = $manager->hierarchyVisibility;
        $visibleSections = $visibilitySettings?->visible_sections ?? [];

        // Load employee with relations based on visibility
        $employee->load(['user']);

        // Prepare data for the view
        $data = [
            'employee' => $employee,
            'email' => $employee->user?->email,
            'visibleSections' => $visibleSections,
        ];

        // Load companies if visible
        if (in_array('companies', $visibleSections)) {
            $employee->load(['employeeCompanies.company', 'employeeCompanies.designation']);
            $data['employeeCompanies'] = $employee->employeeCompanies->map(function ($ec) {
                return [
                    'id' => $ec->id,
                    'company' => $ec->company ? [
                        'id' => $ec->company->id,
                        'company_name' => $ec->company->company_name,
                    ] : null,
                    'designation' => $ec->designation ? [
                        'id' => $ec->designation->id,
                        'designation_name' => $ec->designation->designation_name,
                    ] : null,
                    'status' => $ec->status,
                    'status_label' => $ec->status_label,
                    'commencement_date' => $ec->commencement_date,
                    'left_date' => $ec->left_date,
                    'is_active' => $ec->is_active,
                ];
            });
        }

        // Load stores if visible
        if (in_array('stores', $visibleSections)) {
            $employee->load(['employeeStores.store']);
            $data['stores'] = $employee->employeeStores->map(function ($es) {
                return [
                    'id' => $es->id,
                    'store' => $es->store ? [
                        'id' => $es->store->id,
                        'store_name' => $es->store->store_name,
                        'store_code' => $es->store->store_code,
                    ] : null,
                    'permissions_with_labels' => $es->permissions_with_labels,
                    'active' => $es->active,
                ];
            });
        }

        return Inertia::render('MyTeam/View', $data);
    }

    /**
     * Check if an employee is a subordinate (direct or nested) of a manager.
     */
    private function isSubordinate(Employee $manager, Employee $employee): bool
    {
        // Get all subordinate IDs recursively
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

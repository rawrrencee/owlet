<?php

namespace App\Http\Controllers;

use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeHierarchy;
use App\Models\HierarchyVisibilitySetting;
use App\Services\HierarchyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class OrganisationChartController extends Controller
{
    use RespondsWithInertiaOrJson;

    public function __construct(
        private readonly HierarchyService $hierarchyService
    ) {}

    /**
     * Display the full organisation chart page.
     */
    public function index(): InertiaResponse
    {
        $orgChart = $this->hierarchyService->buildFullOrgChart();

        return Inertia::render('OrganisationChart/Index', [
            'orgChart' => $orgChart,
        ]);
    }

    /**
     * Get the hierarchy data for a specific employee (used in Edit User page).
     */
    public function getEmployeeSubordinates(Request $request, Employee $employee): JsonResponse
    {
        $subordinates = $employee->activeSubordinates()
            ->with(['activeCompanies', 'user'])
            ->get()
            ->map(fn (Employee $sub) => [
                'id' => $sub->id,
                'name' => $sub->full_name,
                'profile_picture_url' => $sub->getProfilePictureUrl(),
                'employee_number' => $sub->employee_number,
                'email' => $sub->user?->email,
            ]);

        $visibilitySettings = $employee->hierarchyVisibility;

        $availableSubordinates = $this->hierarchyService->getAvailableSubordinates($employee)
            ->map(fn (Employee $emp) => [
                'label' => $emp->full_name.($emp->employee_number ? " ({$emp->employee_number})" : ''),
                'value' => $emp->id,
            ]);

        // Build subtree for this employee
        $subtree = $this->hierarchyService->buildOrgChartTree($employee->id);

        return response()->json([
            'subordinates' => $subordinates,
            'subtree' => $subtree,
            'visibility_settings' => $visibilitySettings ? [
                'visible_sections' => $visibilitySettings->visible_sections,
            ] : [
                'visible_sections' => [],
            ],
            'available_sections' => HierarchyVisibilitySetting::getAvailableSections(),
            'available_subordinates' => $availableSubordinates,
        ]);
    }

    /**
     * Add a subordinate to an employee.
     */
    public function addSubordinate(Request $request, Employee $employee): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'subordinate_id' => 'required|exists:employees,id',
        ]);

        $validation = $this->hierarchyService->validateHierarchy($employee->id, $validated['subordinate_id']);

        if (! $validation['valid']) {
            throw ValidationException::withMessages([
                'subordinate_id' => $validation['message'],
            ]);
        }

        EmployeeHierarchy::create([
            'manager_id' => $employee->id,
            'subordinate_id' => $validated['subordinate_id'],
            'active' => true,
        ]);

        return $this->respondWithCreated(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Subordinate added successfully.',
            []
        );
    }

    /**
     * Remove a subordinate from an employee.
     */
    public function removeSubordinate(Request $request, Employee $employee, Employee $subordinate): RedirectResponse|JsonResponse
    {
        $hierarchy = EmployeeHierarchy::where('manager_id', $employee->id)
            ->where('subordinate_id', $subordinate->id)
            ->first();

        if (! $hierarchy) {
            throw ValidationException::withMessages([
                'subordinate_id' => 'This hierarchy relationship does not exist.',
            ]);
        }

        $hierarchy->delete();

        return $this->respondWithDeleted(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Subordinate removed successfully.'
        );
    }

    /**
     * Update visibility settings for a manager.
     */
    public function updateVisibility(Request $request, Employee $employee): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'visible_sections' => 'present|array',
            'visible_sections.*' => 'string|in:'.implode(',', array_keys(HierarchyVisibilitySetting::getAvailableSections())),
        ]);

        HierarchyVisibilitySetting::updateOrCreate(
            ['manager_id' => $employee->id],
            ['visible_sections' => $validated['visible_sections']]
        );

        return $this->respondWithSuccess(
            $request,
            'users.edit',
            ['employee' => $employee->id],
            'Visibility settings updated successfully.',
            []
        );
    }

    /**
     * Display the organisation chart edit page.
     */
    public function edit(): InertiaResponse
    {
        $employees = $this->hierarchyService->getEmployeesWithManagers();
        $companies = Company::whereNull('deleted_at')
            ->where('active', true)
            ->orderBy('company_name')
            ->get(['id', 'company_name']);

        return Inertia::render('OrganisationChart/Edit', [
            'employees' => $employees,
            'companies' => $companies->map(fn (Company $c) => [
                'label' => $c->company_name,
                'value' => $c->id,
            ]),
        ]);
    }

    /**
     * Get the managers and available managers for an employee.
     */
    public function getEmployeeManagers(Employee $employee): JsonResponse
    {
        $availableManagers = $this->hierarchyService->getAvailableManagers($employee)
            ->map(fn (Employee $emp) => [
                'label' => $emp->full_name.($emp->employee_number ? " ({$emp->employee_number})" : ''),
                'value' => $emp->id,
            ]);

        $currentManagers = $employee->activeManagers()
            ->get()
            ->map(fn (Employee $manager) => [
                'id' => $manager->id,
                'name' => $manager->full_name,
                'profile_picture_url' => $manager->getProfilePictureUrl(),
                'employee_number' => $manager->employee_number,
            ]);

        return response()->json([
            'managers' => $currentManagers,
            'available_managers' => $availableManagers,
        ]);
    }

    /**
     * Add a manager to an employee.
     */
    public function addManager(Request $request, Employee $employee): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'manager_id' => 'required|exists:employees,id',
        ]);

        // Validate hierarchy (manager becomes the manager, employee becomes the subordinate)
        $validation = $this->hierarchyService->validateHierarchy($validated['manager_id'], $employee->id);

        if (! $validation['valid']) {
            throw ValidationException::withMessages([
                'manager_id' => $validation['message'],
            ]);
        }

        EmployeeHierarchy::create([
            'manager_id' => $validated['manager_id'],
            'subordinate_id' => $employee->id,
            'active' => true,
        ]);

        return $this->respondWithCreated(
            $request,
            'organisation-chart.edit',
            [],
            'Manager added successfully.',
            []
        );
    }

    /**
     * Remove a manager from an employee.
     */
    public function removeManager(Request $request, Employee $employee, Employee $manager): RedirectResponse|JsonResponse
    {
        $hierarchy = EmployeeHierarchy::where('manager_id', $manager->id)
            ->where('subordinate_id', $employee->id)
            ->first();

        if (! $hierarchy) {
            throw ValidationException::withMessages([
                'manager_id' => 'This hierarchy relationship does not exist.',
            ]);
        }

        $hierarchy->delete();

        return $this->respondWithDeleted(
            $request,
            'organisation-chart.edit',
            [],
            'Manager removed successfully.'
        );
    }

    /**
     * Bulk assign a manager to multiple employees.
     */
    public function bulkAssignManager(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'manager_id' => 'required|exists:employees,id',
        ]);

        $managerId = $validated['manager_id'];
        $employeeIds = $validated['employee_ids'];

        $success = [];
        $failed = [];

        DB::transaction(function () use ($managerId, $employeeIds, &$success, &$failed) {
            foreach ($employeeIds as $employeeId) {
                $employee = Employee::find($employeeId);
                if (! $employee) {
                    $failed[] = [
                        'id' => $employeeId,
                        'name' => 'Unknown',
                        'reason' => 'Employee not found.',
                    ];

                    continue;
                }

                // Validate hierarchy
                $validation = $this->hierarchyService->validateHierarchy($managerId, $employeeId);

                if (! $validation['valid']) {
                    $failed[] = [
                        'id' => $employeeId,
                        'name' => $employee->full_name,
                        'reason' => $validation['message'],
                    ];

                    continue;
                }

                // Create the hierarchy
                EmployeeHierarchy::create([
                    'manager_id' => $managerId,
                    'subordinate_id' => $employeeId,
                    'active' => true,
                ]);

                $success[] = $employeeId;
            }
        });

        return response()->json([
            'success' => $success,
            'failed' => $failed,
        ]);
    }
}

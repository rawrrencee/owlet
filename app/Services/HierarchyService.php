<?php

namespace App\Services;

use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeHierarchy;
use App\Models\HierarchyVisibilitySetting;
use Illuminate\Support\Collection;

class HierarchyService
{
    /**
     * Build an org chart tree starting from a specific employee or all root employees.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildOrgChartTree(?int $rootEmployeeId = null): array
    {
        if ($rootEmployeeId !== null) {
            $rootEmployee = Employee::with(['activeSubordinates', 'activeCompanies'])->find($rootEmployeeId);
            if (! $rootEmployee) {
                return [];
            }

            return [$this->buildNodeRecursive($rootEmployee)];
        }

        // Find all root employees (those who have no managers)
        $allEmployeeIds = Employee::whereNull('termination_date')->pluck('id');
        $subordinateIds = EmployeeHierarchy::where('active', true)->pluck('subordinate_id');
        $rootEmployeeIds = $allEmployeeIds->diff($subordinateIds);

        $roots = Employee::with(['activeSubordinates', 'activeCompanies'])
            ->whereIn('id', $rootEmployeeIds)
            ->whereNull('termination_date')
            ->get();

        return $roots->map(fn (Employee $employee) => $this->buildNodeRecursive($employee))->toArray();
    }

    /**
     * Build an org chart that includes ALL employees (both hierarchy members and standalone).
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildFullOrgChart(): array
    {
        // Get all active employees
        $allEmployees = Employee::with(['activeSubordinates', 'activeCompanies'])
            ->whereNull('termination_date')
            ->get();

        // Find employees who are subordinates (have a manager)
        $subordinateIds = EmployeeHierarchy::where('active', true)->pluck('subordinate_id');

        // Calculate tiers for all employees
        $tiers = $this->calculateAllTiers($allEmployees);

        $nodes = [];

        foreach ($allEmployees as $employee) {
            // Only include as root if they have no manager
            if (! $subordinateIds->contains($employee->id)) {
                $nodes[] = $this->buildNodeRecursive($employee, $tiers);
            }
        }

        return $nodes;
    }

    /**
     * Build a tree node for a single employee recursively.
     *
     * @param  array<int, int>|null  $precomputedTiers
     * @return array<string, mixed>
     */
    private function buildNodeRecursive(Employee $employee, ?array $precomputedTiers = null): array
    {
        $tier = $precomputedTiers[$employee->id] ?? $this->calculateTier($employee);

        $children = [];
        foreach ($employee->activeSubordinates as $subordinate) {
            $children[] = $this->buildNodeRecursive($subordinate, $precomputedTiers);
        }

        // Get designation from the pivot's designation_id
        $designation = null;
        $firstActiveCompany = $employee->activeCompanies->first();
        if ($firstActiveCompany?->pivot?->designation_id) {
            $designation = Designation::find($firstActiveCompany->pivot->designation_id)?->designation_name;
        }

        return [
            'key' => (string) $employee->id,
            'type' => 'employee',
            'data' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'profile_picture_url' => $employee->getProfilePictureUrl(),
                'designation' => $designation,
                'company' => $employee->activeCompanies->first()?->company_name,
                'tier' => $tier,
            ],
            'children' => $children,
        ];
    }

    /**
     * Calculate the tier of an employee.
     * Tier 1 = no subordinates (bottom level)
     * Higher tiers = max(subordinate tiers) + 1
     */
    public function calculateTier(Employee $employee): int
    {
        $subordinates = $employee->activeSubordinates;

        if ($subordinates->isEmpty()) {
            return 1;
        }

        $maxSubordinateTier = 0;
        foreach ($subordinates as $subordinate) {
            $tier = $this->calculateTier($subordinate);
            $maxSubordinateTier = max($maxSubordinateTier, $tier);
        }

        return $maxSubordinateTier + 1;
    }

    /**
     * Calculate tiers for all employees efficiently.
     *
     * @return array<int, int>
     */
    public function calculateAllTiers(Collection $employees): array
    {
        $tiers = [];
        $hierarchies = EmployeeHierarchy::where('active', true)->get();

        // Build adjacency list
        $subordinatesMap = [];
        foreach ($hierarchies as $h) {
            if (! isset($subordinatesMap[$h->manager_id])) {
                $subordinatesMap[$h->manager_id] = [];
            }
            $subordinatesMap[$h->manager_id][] = $h->subordinate_id;
        }

        // Calculate tier for each employee using memoization
        foreach ($employees as $employee) {
            $tiers[$employee->id] = $this->calculateTierRecursive($employee->id, $subordinatesMap, $tiers);
        }

        return $tiers;
    }

    /**
     * Recursive tier calculation with memoization.
     *
     * @param  array<int, array<int>>  $subordinatesMap
     * @param  array<int, int>  $memo
     */
    private function calculateTierRecursive(int $employeeId, array $subordinatesMap, array &$memo): int
    {
        if (isset($memo[$employeeId])) {
            return $memo[$employeeId];
        }

        $subordinateIds = $subordinatesMap[$employeeId] ?? [];

        if (empty($subordinateIds)) {
            $memo[$employeeId] = 1;

            return 1;
        }

        $maxTier = 0;
        foreach ($subordinateIds as $subId) {
            $tier = $this->calculateTierRecursive($subId, $subordinatesMap, $memo);
            $maxTier = max($maxTier, $tier);
        }

        $memo[$employeeId] = $maxTier + 1;

        return $memo[$employeeId];
    }

    /**
     * Validate that adding a hierarchy relationship won't create a circular reference.
     *
     * @return array{valid: bool, message: string}
     */
    public function validateHierarchy(int $managerId, int $subordinateId): array
    {
        // Cannot be your own manager
        if ($managerId === $subordinateId) {
            return [
                'valid' => false,
                'message' => 'An employee cannot be their own manager.',
            ];
        }

        // Check if this relationship already exists
        $exists = EmployeeHierarchy::where('manager_id', $managerId)
            ->where('subordinate_id', $subordinateId)
            ->exists();

        if ($exists) {
            return [
                'valid' => false,
                'message' => 'This hierarchy relationship already exists.',
            ];
        }

        // Check for circular reference: subordinate cannot be an ancestor of manager
        if ($this->isAncestor($subordinateId, $managerId)) {
            return [
                'valid' => false,
                'message' => 'This would create a circular hierarchy. The selected employee is already above this employee in the hierarchy.',
            ];
        }

        return [
            'valid' => true,
            'message' => '',
        ];
    }

    /**
     * Check if potentialAncestor is an ancestor of employee.
     */
    private function isAncestor(int $potentialAncestorId, int $employeeId): bool
    {
        $visited = [];

        return $this->isAncestorRecursive($potentialAncestorId, $employeeId, $visited);
    }

    /**
     * @param  array<int, bool>  $visited
     */
    private function isAncestorRecursive(int $potentialAncestorId, int $currentId, array &$visited): bool
    {
        if (isset($visited[$currentId])) {
            return false;
        }
        $visited[$currentId] = true;

        $managerIds = EmployeeHierarchy::where('subordinate_id', $currentId)
            ->where('active', true)
            ->pluck('manager_id');

        foreach ($managerIds as $managerId) {
            if ($managerId === $potentialAncestorId) {
                return true;
            }
            if ($this->isAncestorRecursive($potentialAncestorId, $managerId, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the full subordinate tree for a manager with visibility-filtered data.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSubordinatesForManager(Employee $manager): array
    {
        $visibilitySettings = $manager->hierarchyVisibility;
        $subordinates = $manager->activeSubordinates;

        return $subordinates->map(fn (Employee $subordinate) => $this->buildSubordinateInfo($subordinate, $visibilitySettings))->toArray();
    }

    /**
     * Get the full subordinate tree for a manager recursively.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSubordinateTreeForManager(Employee $manager): array
    {
        $visibilitySettings = $manager->hierarchyVisibility;

        return $this->buildSubordinateTreeRecursive($manager, $visibilitySettings);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildSubordinateTreeRecursive(Employee $employee, ?HierarchyVisibilitySetting $visibilitySettings): array
    {
        $subordinates = $employee->activeSubordinates()->with(['activeCompanies', 'activeStores'])->get();

        return $subordinates->map(function (Employee $subordinate) use ($visibilitySettings) {
            $info = $this->buildSubordinateInfo($subordinate, $visibilitySettings);
            $info['subordinates'] = $this->buildSubordinateTreeRecursive($subordinate, $visibilitySettings);

            return $info;
        })->toArray();
    }

    /**
     * Build subordinate info with visibility filtering.
     *
     * @return array<string, mixed>
     */
    private function buildSubordinateInfo(Employee $subordinate, ?HierarchyVisibilitySetting $visibilitySettings): array
    {
        $info = [
            'id' => $subordinate->id,
            'name' => $subordinate->full_name,
            'profile_picture_url' => $subordinate->getProfilePictureUrl(),
            'employee_number' => $subordinate->employee_number,
            'email' => $subordinate->user?->email,
            'phone' => $subordinate->phone ?: $subordinate->mobile,
        ];

        // Add companies if visible
        if ($visibilitySettings?->canView('companies')) {
            $info['companies'] = $subordinate->activeCompanies->map(fn ($company) => [
                'id' => $company->id,
                'name' => $company->company_name,
            ])->toArray();
        }

        // Add stores if visible
        if ($visibilitySettings?->canView('stores')) {
            $info['stores'] = $subordinate->activeStores->map(fn ($store) => [
                'id' => $store->id,
                'name' => $store->store_name,
            ])->toArray();
        }

        return $info;
    }

    /**
     * Get all employees that can be added as subordinates for a manager.
     * Excludes: the manager themselves, existing subordinates, and ancestors.
     *
     * @return Collection<int, Employee>
     */
    public function getAvailableSubordinates(Employee $manager): Collection
    {
        $managerId = $manager->id;

        // Get existing subordinate IDs
        $existingSubordinateIds = $manager->activeSubordinates()->pluck('employees.id');

        // Get all ancestor IDs (to prevent circular references)
        $ancestorIds = $this->getAllAncestorIds($managerId);

        return Employee::whereNull('termination_date')
            ->where('id', '!=', $managerId)
            ->whereNotIn('id', $existingSubordinateIds)
            ->whereNotIn('id', $ancestorIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Get all ancestor IDs for an employee.
     *
     * @return array<int>
     */
    private function getAllAncestorIds(int $employeeId): array
    {
        $ancestors = [];
        $visited = [];
        $this->collectAncestors($employeeId, $ancestors, $visited);

        return $ancestors;
    }

    /**
     * @param  array<int>  $ancestors
     * @param  array<int, bool>  $visited
     */
    private function collectAncestors(int $employeeId, array &$ancestors, array &$visited): void
    {
        if (isset($visited[$employeeId])) {
            return;
        }
        $visited[$employeeId] = true;

        $managerIds = EmployeeHierarchy::where('subordinate_id', $employeeId)
            ->where('active', true)
            ->pluck('manager_id');

        foreach ($managerIds as $managerId) {
            $ancestors[] = $managerId;
            $this->collectAncestors($managerId, $ancestors, $visited);
        }
    }

    /**
     * Get all employees that can be managers for an employee.
     * Excludes: the employee themselves, current managers, and descendants (to prevent circular refs).
     *
     * @return Collection<int, Employee>
     */
    public function getAvailableManagers(Employee $employee): Collection
    {
        $employeeId = $employee->id;

        // Get existing manager IDs
        $existingManagerIds = $employee->activeManagers()->pluck('employees.id');

        // Get all descendant IDs (to prevent circular references)
        $descendantIds = $this->getAllDescendantIds($employeeId);

        return Employee::whereNull('termination_date')
            ->where('id', '!=', $employeeId)
            ->whereNotIn('id', $existingManagerIds)
            ->whereNotIn('id', $descendantIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Get all descendant IDs for an employee.
     *
     * @return array<int>
     */
    private function getAllDescendantIds(int $employeeId): array
    {
        $descendants = [];
        $visited = [];
        $this->collectDescendants($employeeId, $descendants, $visited);

        return $descendants;
    }

    /**
     * @param  array<int>  $descendants
     * @param  array<int, bool>  $visited
     */
    private function collectDescendants(int $employeeId, array &$descendants, array &$visited): void
    {
        if (isset($visited[$employeeId])) {
            return;
        }
        $visited[$employeeId] = true;

        $subordinateIds = EmployeeHierarchy::where('manager_id', $employeeId)
            ->where('active', true)
            ->pluck('subordinate_id');

        foreach ($subordinateIds as $subordinateId) {
            $descendants[] = $subordinateId;
            $this->collectDescendants($subordinateId, $descendants, $visited);
        }
    }

    /**
     * Get employees list with their managers and tiers for edit page.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getEmployeesWithManagers(): Collection
    {
        $employees = Employee::with(['activeManagers', 'activeCompanies'])
            ->whereNull('termination_date')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Calculate tiers for all employees
        $tiers = $this->calculateAllTiers($employees);

        return $employees->map(function (Employee $employee) use ($tiers) {
            // Get designation from the first active company
            $designation = null;
            $companyName = null;
            $firstActiveCompany = $employee->activeCompanies->first();
            if ($firstActiveCompany) {
                $companyName = $firstActiveCompany->company_name;
                if ($firstActiveCompany->pivot?->designation_id) {
                    $designation = Designation::find($firstActiveCompany->pivot->designation_id)?->designation_name;
                }
            }

            return [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'profile_picture_url' => $employee->getProfilePictureUrl(),
                'employee_number' => $employee->employee_number,
                'designation' => $designation,
                'company' => $companyName,
                'tier' => $tiers[$employee->id] ?? 1,
                'managers' => $employee->activeManagers->map(fn (Employee $manager) => [
                    'id' => $manager->id,
                    'name' => $manager->full_name,
                    'profile_picture_url' => $manager->getProfilePictureUrl(),
                    'employee_number' => $manager->employee_number,
                ])->values()->toArray(),
            ];
        });
    }
}

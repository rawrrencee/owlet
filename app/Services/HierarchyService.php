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
        // Load all active employees with companies in a single query
        $allEmployees = Employee::with(['activeCompanies'])
            ->whereNull('termination_date')
            ->get()
            ->keyBy('id');

        // Load all active hierarchies
        $hierarchies = EmployeeHierarchy::where('active', true)->get();

        // Build adjacency map
        $subordinatesMap = [];
        $subordinateIdsSet = [];
        foreach ($hierarchies as $h) {
            $subordinatesMap[$h->manager_id][] = $h->subordinate_id;
            $subordinateIdsSet[$h->subordinate_id] = true;
        }

        // Preload designations
        $designationIds = $allEmployees->flatMap(function ($emp) {
            return $emp->activeCompanies->pluck('pivot.designation_id');
        })->filter()->unique()->values()->all();

        $designations = Designation::whereIn('id', $designationIds)->pluck('designation_name', 'id');

        // Calculate tiers
        $tiers = $this->calculateAllTiers($allEmployees);

        if ($rootEmployeeId !== null) {
            if (! $allEmployees->has($rootEmployeeId)) {
                return [];
            }

            return [$this->buildNodeFromMap($rootEmployeeId, $allEmployees, $subordinatesMap, $tiers, $designations)];
        }

        // Find all root employees (those who have no managers)
        $nodes = [];
        foreach ($allEmployees as $employee) {
            if (! isset($subordinateIdsSet[$employee->id])) {
                $nodes[] = $this->buildNodeFromMap($employee->id, $allEmployees, $subordinatesMap, $tiers, $designations);
            }
        }

        return $nodes;
    }

    /**
     * Build an org chart that includes ALL employees (both hierarchy members and standalone).
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildFullOrgChart(): array
    {
        // Load all active employees with companies in a single query
        $allEmployees = Employee::with(['activeCompanies'])
            ->whereNull('termination_date')
            ->get()
            ->keyBy('id');

        // Load all active hierarchies in a single query
        $hierarchies = EmployeeHierarchy::where('active', true)->get();

        // Build adjacency map: manager_id => [subordinate_ids]
        $subordinatesMap = [];
        $subordinateIds = [];
        foreach ($hierarchies as $h) {
            $subordinatesMap[$h->manager_id][] = $h->subordinate_id;
            $subordinateIds[$h->subordinate_id] = true;
        }

        // Preload all designations in a single query
        $designationIds = $allEmployees->flatMap(function ($emp) {
            return $emp->activeCompanies->pluck('pivot.designation_id');
        })->filter()->unique()->values()->all();

        $designations = Designation::whereIn('id', $designationIds)->pluck('designation_name', 'id');

        // Calculate tiers
        $tiers = $this->calculateAllTiers($allEmployees);

        $nodes = [];

        foreach ($allEmployees as $employee) {
            // Only include as root if they have no manager
            if (! isset($subordinateIds[$employee->id])) {
                $nodes[] = $this->buildNodeFromMap($employee->id, $allEmployees, $subordinatesMap, $tiers, $designations);
            }
        }

        return $nodes;
    }

    /**
     * Build a tree node using preloaded data maps (no recursive DB queries).
     *
     * @param  \Illuminate\Support\Collection<int, Employee>  $employeesMap
     * @param  array<int, array<int>>  $subordinatesMap
     * @param  array<int, int>  $tiers
     * @param  \Illuminate\Support\Collection<int, string>  $designations
     * @param  array<int, bool>  $visited  Cycle detection
     * @return array<string, mixed>
     */
    private function buildNodeFromMap(
        int $employeeId,
        \Illuminate\Support\Collection $employeesMap,
        array $subordinatesMap,
        array $tiers,
        \Illuminate\Support\Collection $designations,
        array &$visited = [],
    ): array {
        // Cycle detection
        if (isset($visited[$employeeId])) {
            $name = $employeesMap[$employeeId]?->full_name ?? 'Unknown';

            return [
                'key' => (string) $employeeId,
                'label' => $name,
                'type' => 'employee',
                'data' => [
                    'id' => $employeeId,
                    'name' => $name,
                    'profile_picture_url' => null,
                    'designation' => null,
                    'company' => null,
                    'tier' => $tiers[$employeeId] ?? 1,
                ],
                'children' => [],
            ];
        }
        $visited[$employeeId] = true;

        $employee = $employeesMap[$employeeId] ?? null;
        if (! $employee) {
            return [
                'key' => (string) $employeeId,
                'label' => 'Unknown',
                'type' => 'employee',
                'data' => [
                    'id' => $employeeId,
                    'name' => 'Unknown',
                    'profile_picture_url' => null,
                    'designation' => null,
                    'company' => null,
                    'tier' => 1,
                ],
                'children' => [],
            ];
        }

        $children = [];
        $childIds = $subordinatesMap[$employeeId] ?? [];
        foreach ($childIds as $childId) {
            $children[] = $this->buildNodeFromMap($childId, $employeesMap, $subordinatesMap, $tiers, $designations, $visited);
        }

        // Get designation from preloaded map
        $designation = null;
        $firstActiveCompany = $employee->activeCompanies->first();
        if ($firstActiveCompany?->pivot?->designation_id) {
            $designation = $designations[$firstActiveCompany->pivot->designation_id] ?? null;
        }

        $labelParts = [$employee->full_name];
        if ($designation) {
            $labelParts[] = $designation;
        }
        if ($firstActiveCompany?->company_name) {
            $labelParts[] = $firstActiveCompany->company_name;
        }

        return [
            'key' => (string) $employee->id,
            'label' => implode(' · ', $labelParts),
            'type' => 'employee',
            'data' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'profile_picture_url' => $employee->getProfilePictureUrl(),
                'designation' => $designation,
                'company' => $firstActiveCompany?->company_name,
                'tier' => $tiers[$employee->id] ?? 1,
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
        $visiting = []; // Track nodes currently in the recursion stack for cycle detection
        foreach ($employees as $employee) {
            $tiers[$employee->id] = $this->calculateTierRecursive($employee->id, $subordinatesMap, $tiers, $visiting);
        }

        return $tiers;
    }

    /**
     * Recursive tier calculation with memoization and cycle detection.
     *
     * @param  array<int, array<int>>  $subordinatesMap
     * @param  array<int, int>  $memo
     * @param  array<int, bool>  $visiting  Nodes in current recursion stack
     */
    private function calculateTierRecursive(int $employeeId, array $subordinatesMap, array &$memo, array &$visiting): int
    {
        if (isset($memo[$employeeId])) {
            return $memo[$employeeId];
        }

        // Cycle detected — this node is already being visited in the current stack
        if (isset($visiting[$employeeId])) {
            $memo[$employeeId] = 1;

            return 1;
        }

        $subordinateIds = $subordinatesMap[$employeeId] ?? [];

        if (empty($subordinateIds)) {
            $memo[$employeeId] = 1;

            return 1;
        }

        $visiting[$employeeId] = true;

        $maxTier = 0;
        foreach ($subordinateIds as $subId) {
            $tier = $this->calculateTierRecursive($subId, $subordinatesMap, $memo, $visiting);
            $maxTier = max($maxTier, $tier);
        }

        unset($visiting[$employeeId]);

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

        // Preload all designations in a single query
        $designationIds = $employees->flatMap(function ($emp) {
            return $emp->activeCompanies->pluck('pivot.designation_id');
        })->filter()->unique()->values()->all();

        $designations = Designation::whereIn('id', $designationIds)->pluck('designation_name', 'id');

        // Calculate tiers for all employees
        $tiers = $this->calculateAllTiers($employees);

        return $employees->map(function (Employee $employee) use ($tiers, $designations) {
            // Get designation from preloaded map
            $designation = null;
            $companyName = null;
            $firstActiveCompany = $employee->activeCompanies->first();
            if ($firstActiveCompany) {
                $companyName = $firstActiveCompany->company_name;
                if ($firstActiveCompany->pivot?->designation_id) {
                    $designation = $designations[$firstActiveCompany->pivot->designation_id] ?? null;
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

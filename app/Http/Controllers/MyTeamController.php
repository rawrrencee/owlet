<?php

namespace App\Http\Controllers;

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
}

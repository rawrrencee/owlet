<?php

namespace App\Http\Controllers;

use App\Http\Resources\TimecardResource;
use App\Services\TimecardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TimecardService $timecardService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        $data = [];

        if ($employee) {
            // Get current timecard state for clock widget
            $currentState = $this->timecardService->getCurrentTimecardState($employee);

            $data['currentTimecard'] = $currentState['timecard']
                ? (new TimecardResource($currentState['timecard']))->resolve()
                : null;
            $data['isOnBreak'] = $currentState['is_on_break'];

            // Get employee's assigned stores for clock in dropdown
            $data['stores'] = $employee->activeStores()
                ->select('stores.id', 'stores.store_name', 'stores.store_code')
                ->get()
                ->map(fn ($store) => [
                    'id' => $store->id,
                    'name' => $store->store_name,
                    'code' => $store->store_code,
                ]);
        }

        return Inertia::render('Dashboard', $data);
    }
}

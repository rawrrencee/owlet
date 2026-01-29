<?php

namespace App\Http\Middleware;

use App\Http\Resources\TimecardResource;
use App\Models\Timecard;
use App\Services\NavigationService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user?->load(['employee', 'customer']),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'navigation' => $user
                ? app(NavigationService::class)->getMainNavItems($user)
                : [],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'incompleteTimecards' => fn () => $user?->employee_id
                ? TimecardResource::collection(
                    Timecard::where('employee_id', $user->employee_id)
                        ->needsResolution()
                        ->with('store')
                        ->orderBy('start_date', 'desc')
                        ->get()
                )->resolve()
                : [],
        ];
    }
}

<?php

namespace App\Services;

use App\Enums\LeaveRequestStatus;
use App\Models\DeliveryOrder;
use App\Models\Employee;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\Stocktake;
use App\Models\Timecard;
use App\Models\User;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        private readonly PermissionService $permissionService,
        private readonly AnalyticsService $analyticsService,
    ) {}

    public function getWidgetData(User $user): array
    {
        $employee = $user->employee;
        $data = [];

        if ($employee) {
            $data['weeklyTimecard'] = $this->getWeeklyTimecard($employee);
            $data['upcomingLeave'] = $this->getUpcomingLeave($employee);

            if ($employee->hasSubordinates()) {
                $data['teamPresence'] = $this->getTeamPresence($employee);
            }
        }

        if ($this->permissionService->canAccessPage($user, 'pos.access')) {
            $data['salesPerformance'] = $this->getSalesPerformance($user);
        }

        if ($employee) {
            $data['recentActivity'] = $this->getRecentActivity($employee);
        }

        $data['quickLinks'] = $this->getQuickLinks($user);

        return $data;
    }

    private function getWeeklyTimecard(Employee $employee): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $timecards = Timecard::where('employee_id', $employee->id)
            ->forDateRange($startOfWeek, $endOfWeek)
            ->with('details')
            ->get();

        $days = [];
        for ($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay()) {
            $dayTimecards = $timecards->filter(
                fn ($tc) => $tc->start_date->toDateString() === $date->toDateString()
            );

            $totalMinutes = 0;
            foreach ($dayTimecards as $tc) {
                foreach ($tc->details as $detail) {
                    if ($detail->type === 'work' && $detail->end_time) {
                        $totalMinutes += Carbon::parse($detail->start_time)
                            ->diffInMinutes(Carbon::parse($detail->end_time));
                    }
                }
            }

            $days[] = [
                'date' => $date->toDateString(),
                'day_name' => $date->format('D'),
                'hours' => round($totalMinutes / 60, 1),
                'is_today' => $date->isToday(),
            ];
        }

        $totalWeekHours = collect($days)->sum('hours');

        return [
            'days' => $days,
            'total_hours' => round($totalWeekHours, 1),
        ];
    }

    private function getTeamPresence(Employee $employee): array
    {
        $subordinates = $employee->activeSubordinates()
            ->select('employees.id', 'employees.first_name', 'employees.last_name', 'employees.profile_picture', 'employees.external_avatar_url')
            ->get();

        $timedInIds = Timecard::whereIn('employee_id', $subordinates->pluck('id'))
            ->where('status', Timecard::STATUS_IN_PROGRESS)
            ->whereDate('start_date', today())
            ->pluck('employee_id')
            ->toArray();

        return $subordinates->map(fn ($sub) => [
            'id' => $sub->id,
            'name' => $sub->first_name . ' ' . $sub->last_name,
            'profile_picture_url' => $sub->getProfilePictureUrl(),
            'is_timed_in' => in_array($sub->id, $timedInIds),
        ])->toArray();
    }

    private function getSalesPerformance(User $user): ?array
    {
        $storeIds = $this->permissionService->getAccessibleStoreIds($user);
        if (empty($storeIds)) {
            return null;
        }

        // Use first accessible store's default currency for the dashboard summary
        $store = \App\Models\Store::find($storeIds[0]);
        if (! $store) {
            return null;
        }

        $defaultCurrency = $store->currencies()->first();
        if (! $defaultCurrency) {
            return null;
        }

        $from = Carbon::now()->startOfMonth()->toDateString();
        $to = Carbon::now()->toDateString();

        try {
            $widgets = $this->analyticsService->getSalesWidgets(
                $from,
                $to,
                null, // All accessible stores
                $defaultCurrency->id,
            );

            return [
                'total_sales' => $widgets['total_sales'],
                'transaction_count' => $widgets['transaction_count'],
                'avg_order_value' => $widgets['avg_order_value'],
                'prev_total_sales' => $widgets['prev_total_sales'],
                'currency_symbol' => $defaultCurrency->symbol ?? $defaultCurrency->code,
                'period_label' => Carbon::now()->format('F Y'),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function getUpcomingLeave(Employee $employee): array
    {
        return $employee->leaveRequests()
            ->with('leaveType:id,name,color')
            ->whereIn('status', [LeaveRequestStatus::PENDING, LeaveRequestStatus::APPROVED])
            ->where('start_date', '>=', today())
            ->orderBy('start_date')
            ->limit(5)
            ->get()
            ->map(fn ($lr) => [
                'id' => $lr->id,
                'type_name' => $lr->leaveType?->name ?? 'Unknown',
                'type_color' => $lr->leaveType?->color,
                'start_date' => $lr->start_date->toDateString(),
                'end_date' => $lr->end_date->toDateString(),
                'total_days' => $lr->total_days,
                'status' => $lr->status->value,
                'status_label' => $lr->status->label(),
            ])
            ->toArray();
    }

    private function getRecentActivity(Employee $employee): array
    {
        $userId = $employee->user?->id;
        if (! $userId) {
            return [];
        }

        $activities = collect();

        // Recent delivery orders
        $activities = $activities->merge(
            DeliveryOrder::where('submitted_by', $userId)
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn ($do) => [
                    'type' => 'delivery_order',
                    'label' => "Delivery Order #{$do->do_number}",
                    'status' => $do->status->value,
                    'date' => $do->created_at->toIso8601String(),
                    'url' => "/delivery-orders/{$do->id}",
                ])
        );

        // Recent purchase orders
        $activities = $activities->merge(
            PurchaseOrder::where('submitted_by', $userId)
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn ($po) => [
                    'type' => 'purchase_order',
                    'label' => "Purchase Order #{$po->po_number}",
                    'status' => $po->status->value,
                    'date' => $po->created_at->toIso8601String(),
                    'url' => "/purchase-orders/{$po->id}",
                ])
        );

        // Recent stocktakes
        $activities = $activities->merge(
            Stocktake::where('employee_id', $employee->id)
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn ($st) => [
                    'type' => 'stocktake',
                    'label' => 'Stocktake #' . $st->id,
                    'status' => $st->status->value,
                    'date' => $st->created_at->toIso8601String(),
                    'url' => "/stocktakes/{$st->id}",
                ])
        );

        // Recent quotations
        $activities = $activities->merge(
            Quotation::where('created_by', $userId)
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn ($q) => [
                    'type' => 'quotation',
                    'label' => "Quotation #{$q->quotation_number}",
                    'status' => $q->status->value,
                    'date' => $q->created_at->toIso8601String(),
                    'url' => "/quotations/{$q->id}",
                ])
        );

        return $activities->sortByDesc('date')->take(8)->values()->toArray();
    }

    private function getQuickLinks(User $user): array
    {
        $links = [];

        $links[] = ['label' => 'My Timecards', 'href' => '/timecards', 'icon' => 'Clock'];
        $links[] = ['label' => 'My Leave', 'href' => '/leave', 'icon' => 'CalendarDays'];

        if ($this->permissionService->canAccessPage($user, 'pos.access')) {
            $links[] = ['label' => 'Point of Sale', 'href' => '/pos', 'icon' => 'ShoppingBag'];
        }
        if ($this->permissionService->canAccessPage($user, 'products.view')) {
            $links[] = ['label' => 'Products', 'href' => '/products', 'icon' => 'Package'];
        }
        if ($this->permissionService->canAccessPage($user, 'stocktakes.submit')) {
            $links[] = ['label' => 'Stocktake', 'href' => '/stocktakes', 'icon' => 'ClipboardCheck'];
        }
        if ($this->permissionService->canAccessPage($user, 'analytics.view')) {
            $links[] = ['label' => 'Analytics', 'href' => '/analytics', 'icon' => 'BarChart3'];
        }
        if ($user->employee?->hasSubordinates()) {
            $links[] = ['label' => 'My Team', 'href' => '/my-team', 'icon' => 'UsersRound'];
        }

        return $links;
    }
}

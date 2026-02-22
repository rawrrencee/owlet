<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Store;
use App\Models\UserSetting;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $stores = Store::orderBy('store_name')->get(['id', 'store_name', 'store_code']);
        $currencies = Currency::orderBy('code')->get(['id', 'code', 'symbol', 'name']);

        [$from, $to, $storeId, $currencyId, $granularity] = $this->resolveFilters($request, $currencies);

        $data = [];
        if ($currencyId) {
            $data = [
                'widgets' => $this->analyticsService->getSalesWidgets($from, $to, $storeId, $currencyId),
                'salesOverTime' => $this->analyticsService->getSalesOverTime($from, $to, $granularity, $storeId, $currencyId),
                'salesByStore' => $this->analyticsService->getSalesByStore($from, $to, $currencyId),
                'topProducts' => $this->analyticsService->getTopProducts($from, $to, $storeId, $currencyId),
                'salesByPaymentMethod' => $this->analyticsService->getSalesByPaymentMethod($from, $to, $storeId, $currencyId),
                'discountBreakdown' => $this->analyticsService->getDiscountBreakdown($from, $to, $storeId, $currencyId),
                'employeePerformance' => $this->analyticsService->getEmployeePerformance($from, $to, $storeId, $currencyId),
            ];
        }

        return Inertia::render('Analytics/Index', [
            'stores' => $stores,
            'currencies' => $currencies,
            'data' => $data,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'store_id' => $storeId,
                'currency_id' => $currencyId,
                'granularity' => $granularity,
            ],
        ]);
    }

    public function employees(Request $request): InertiaResponse
    {
        $stores = Store::orderBy('store_name')->get(['id', 'store_name', 'store_code']);
        $currencies = Currency::orderBy('code')->get(['id', 'code', 'symbol', 'name']);

        [$from, $to, $storeId, $currencyId] = $this->resolveFilters($request, $currencies);

        $data = [];
        if ($currencyId) {
            $data = [
                'employeePerformance' => $this->analyticsService->getEmployeePerformance($from, $to, $storeId, $currencyId),
            ];
        }

        return Inertia::render('Analytics/Employees', [
            'stores' => $stores,
            'currencies' => $currencies,
            'data' => $data,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'store_id' => $storeId,
                'currency_id' => $currencyId,
            ],
        ]);
    }

    public function products(Request $request): InertiaResponse
    {
        $stores = Store::orderBy('store_name')->get(['id', 'store_name', 'store_code']);
        $currencies = Currency::orderBy('code')->get(['id', 'code', 'symbol', 'name']);

        [$from, $to, $storeId, $currencyId] = $this->resolveFilters($request, $currencies);

        $data = [];
        if ($currencyId) {
            $data = [
                'topProducts' => $this->analyticsService->getTopProducts($from, $to, $storeId, $currencyId, 25),
            ];
        }

        return Inertia::render('Analytics/Products', [
            'stores' => $stores,
            'currencies' => $currencies,
            'data' => $data,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'store_id' => $storeId,
                'currency_id' => $currencyId,
            ],
        ]);
    }

    /**
     * Save analytics filter preferences for the current user.
     */
    public function savePreferences(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        if ($request->filled('store_id')) {
            UserSetting::set($userId, 'analytics_store_id', $request->input('store_id'));
        } else {
            UserSetting::set($userId, 'analytics_store_id', null);
        }

        if ($request->filled('currency_id')) {
            UserSetting::set($userId, 'analytics_currency_id', $request->input('currency_id'));
        }

        if ($request->filled('granularity')) {
            UserSetting::set($userId, 'analytics_granularity', $request->input('granularity'));
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Resolve filters from request, falling back to saved preferences, then system defaults.
     */
    private function resolveFilters(Request $request, $currencies): array
    {
        $userId = $request->user()->id;

        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::now()->toDateString());

        // Store: request → saved preference → null (all stores)
        if ($request->filled('store_id')) {
            $storeId = $request->integer('store_id');
        } elseif (! $request->hasAny(['from', 'to', 'currency_id', 'granularity'])) {
            // Only use saved preference on initial page load (no query params at all)
            $savedStore = UserSetting::get($userId, 'analytics_store_id');
            $storeId = $savedStore ? (int) $savedStore : null;
        } else {
            $storeId = null;
        }

        // Currency: request → saved preference → SGD → first available
        if ($request->filled('currency_id')) {
            $currencyId = $request->integer('currency_id');
        } elseif (! $request->hasAny(['from', 'to', 'store_id', 'granularity'])) {
            $savedCurrency = UserSetting::get($userId, 'analytics_currency_id');
            if ($savedCurrency) {
                $currencyId = (int) $savedCurrency;
            } else {
                $sgd = $currencies->firstWhere('code', 'SGD');
                $currencyId = $sgd?->id ?? $currencies->first()?->id;
            }
        } else {
            $sgd = $currencies->firstWhere('code', 'SGD');
            $currencyId = $sgd?->id ?? $currencies->first()?->id;
        }

        // Granularity: request → saved preference → daily
        if ($request->filled('granularity')) {
            $granularity = $request->input('granularity');
        } elseif (! $request->hasAny(['from', 'to', 'store_id', 'currency_id'])) {
            $granularity = UserSetting::get($userId, 'analytics_granularity', 'daily');
        } else {
            $granularity = 'daily';
        }

        return [$from, $to, $storeId, $currencyId, $granularity];
    }
}

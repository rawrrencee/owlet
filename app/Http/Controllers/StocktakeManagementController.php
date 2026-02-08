<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustInventoryRequest;
use App\Http\Resources\StocktakeItemResource;
use App\Http\Resources\StocktakeResource;
use App\Models\Product;
use App\Models\Stocktake;
use App\Models\StocktakeItem;
use App\Models\Store;
use App\Services\PermissionService;
use App\Services\StocktakeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class StocktakeManagementController extends Controller
{
    public function __construct(
        private readonly StocktakeService $stocktakeService,
        private readonly PermissionService $permissionService
    ) {}

    /**
     * Management page with filters and tabs.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $storeId = $request->query('store_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $tab = $request->query('tab', 'by-employee');
        $search = $request->query('search');
        $page = (int) $request->query('page', 1);
        $perPage = 15;

        // Get accessible stores
        $accessibleStoreIds = $this->permissionService->getAccessibleStoreIds($user);
        $stores = Store::whereIn('id', $accessibleStoreIds)
            ->select('id', 'store_name', 'store_code')
            ->orderBy('store_name')
            ->get();

        $data = [];

        if ($tab === 'by-employee') {
            $data = $this->getByEmployeeData($accessibleStoreIds, $storeId, $startDate, $endDate, $page, $perPage);
        } else {
            $data = $this->getByItemData($accessibleStoreIds, $storeId, $startDate, $endDate, $search, $page, $perPage);
        }

        $canViewDifference = $storeId
            ? $this->stocktakeService->canViewDifference($user, (int) $storeId)
            : $user->isAdmin();

        $canAdjustQuantity = $this->permissionService->canAccessPage($user, 'stocktakes.lost_and_found');

        return Inertia::render('Management/Stocktakes/Index', [
            'stores' => $stores,
            'filters' => [
                'store_id' => $storeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'tab' => $tab,
                'search' => $search,
            ],
            'data' => $data,
            'canViewDifference' => $canViewDifference,
            'canAdjustQuantity' => $canAdjustQuantity,
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    /**
     * Show a submitted stocktake detail.
     */
    public function show(Request $request, Stocktake $stocktake): InertiaResponse
    {
        $user = $request->user();

        // Check store access
        $accessibleStoreIds = $this->permissionService->getAccessibleStoreIds($user);
        if (! in_array($stocktake->store_id, $accessibleStoreIds)) {
            abort(403, 'You do not have access to this store.');
        }

        $stocktake->load(['items.product', 'employee', 'store']);

        $canViewDifference = $this->stocktakeService->canViewDifference($user, $stocktake->store_id);
        $canAdjustQuantity = $this->permissionService->canAccessPage($user, 'stocktakes.lost_and_found');

        return Inertia::render('Management/Stocktakes/Show', [
            'stocktake' => StocktakeResource::make($stocktake)->resolve(),
            'canViewDifference' => $canViewDifference,
            'canAdjustQuantity' => $canAdjustQuantity,
        ]);
    }

    /**
     * Adjust inventory quantity (Lost/Found).
     */
    public function adjustQuantity(AdjustInventoryRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $product = Product::findOrFail($validated['product_id']);
        $store = Store::findOrFail($validated['store_id']);
        $stocktake = isset($validated['stocktake_id'])
            ? Stocktake::find($validated['stocktake_id'])
            : null;

        $log = $this->stocktakeService->adjustQuantity(
            $product,
            $store,
            $validated['adjust_quantity'],
            $stocktake,
            $user
        );

        if (isset($validated['notes']) && $validated['notes']) {
            $log->update(['notes' => $validated['notes']]);
        }

        return back()->with('success', 'Inventory quantity adjusted successfully.');
    }

    /**
     * Get stocktake data grouped by employee.
     */
    protected function getByEmployeeData(array $storeIds, ?string $storeId, ?string $startDate, ?string $endDate, int $page, int $perPage): array
    {
        $query = Stocktake::submitted()
            ->whereIn('store_id', $storeIds)
            ->with(['employee', 'store'])
            ->withCount('items');

        if ($storeId) {
            $query->forStore((int) $storeId);
        }

        if ($startDate || $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        $stocktakes = $query->orderByDesc('submitted_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => StocktakeResource::collection($stocktakes)->resolve(),
            'pagination' => [
                'current_page' => $stocktakes->currentPage(),
                'last_page' => $stocktakes->lastPage(),
                'per_page' => $stocktakes->perPage(),
                'total' => $stocktakes->total(),
            ],
        ];
    }

    /**
     * Get stocktake data grouped by item/product.
     */
    protected function getByItemData(array $storeIds, ?string $storeId, ?string $startDate, ?string $endDate, ?string $search, int $page, int $perPage): array
    {
        $query = StocktakeItem::query()
            ->whereHas('stocktake', function ($q) use ($storeIds, $storeId, $startDate, $endDate) {
                $q->submitted()->whereIn('store_id', $storeIds);

                if ($storeId) {
                    $q->forStore((int) $storeId);
                }

                if ($startDate || $endDate) {
                    $q->dateRange($startDate, $endDate);
                }
            })
            ->with(['product', 'stocktake.employee', 'stocktake.store']);

        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->search($search);
            });
        }

        // Get latest stocktake item per product (subquery approach)
        $latestItems = $query->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => StocktakeItemResource::collection($latestItems)->resolve(),
            'pagination' => [
                'current_page' => $latestItems->currentPage(),
                'last_page' => $latestItems->lastPage(),
                'per_page' => $latestItems->perPage(),
                'total' => $latestItems->total(),
            ],
        ];
    }
}

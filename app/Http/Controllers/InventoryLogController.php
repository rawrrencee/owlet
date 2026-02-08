<?php

namespace App\Http\Controllers;

use App\Constants\InventoryActivityCodes;
use App\Http\Resources\InventoryLogResource;
use App\Models\Category;
use App\Models\InventoryLog;
use App\Models\Store;
use App\Models\Subcategory;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class InventoryLogController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissionService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $query = InventoryLog::with([
            'product', 'store', 'createdByUser',
            'deliveryOrder', 'purchaseOrder',
        ]);

        // Filter by accessible stores for non-admin users
        if (! $user->isAdmin()) {
            $accessibleStoreIds = $this->permissionService->getAccessibleStoreIds($user);
            $query->whereIn('store_id', $accessibleStoreIds);
        }

        // Filters
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }
        if ($request->filled('activity_code')) {
            $query->where('activity_code', $request->activity_code);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->withTrashed()
                    ->where('product_name', 'like', "%{$search}%")
                    ->orWhere('product_number', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }
        if ($request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->withTrashed()->where('category_id', $request->category_id);
            });
        }
        if ($request->filled('subcategory_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->withTrashed()->where('subcategory_id', $request->subcategory_id);
            });
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $logs = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20))
            ->withQueryString();

        $stores = Store::select('id', 'store_name', 'store_code')
            ->orderBy('store_name')
            ->get();

        $categories = Category::select('id', 'category_name')
            ->orderBy('category_name')
            ->get();

        $subcategories = [];
        if ($request->filled('category_id')) {
            $subcategories = Subcategory::select('id', 'subcategory_name')
                ->where('category_id', $request->category_id)
                ->orderBy('subcategory_name')
                ->get();
        }

        return Inertia::render('InventoryLogs/Index', [
            'logs' => InventoryLogResource::collection($logs),
            'stores' => $stores,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'activityCodes' => InventoryActivityCodes::all(),
            'filters' => $request->only(['store_id', 'activity_code', 'search', 'category_id', 'subcategory_id', 'start_date', 'end_date']),
        ]);
    }
}

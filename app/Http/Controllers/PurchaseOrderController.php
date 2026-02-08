<?php

namespace App\Http\Controllers;

use App\Constants\StorePermissions;
use App\Enums\PurchaseOrderStatus;
use App\Http\Requests\AcceptPurchaseOrderRequest;
use App\Http\Requests\RejectPurchaseOrderRequest;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\Currency;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\PermissionService;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderService $service,
        private readonly PermissionService $permissionService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $query = PurchaseOrder::with(['supplier', 'store', 'createdBy'])
            ->withCount('items');

        // Filters
        if ($request->filled('supplier_id')) {
            $query->forSupplier((int) $request->supplier_id);
        }
        if ($request->filled('store_id')) {
            $query->forStore((int) $request->store_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('order_number', 'like', "%{$search}%");
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $orders = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        $suppliers = Supplier::select('id', 'supplier_name')
            ->where('active', true)
            ->orderBy('supplier_name')
            ->get();

        $stores = Store::select('id', 'store_name', 'store_code')
            ->orderBy('store_name')
            ->get();

        return Inertia::render('PurchaseOrders/Index', [
            'orders' => PurchaseOrderResource::collection($orders),
            'suppliers' => $suppliers,
            'stores' => $stores,
            'filters' => $request->only(['supplier_id', 'store_id', 'status', 'search', 'start_date', 'end_date']),
            'statusOptions' => PurchaseOrderStatus::options(),
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $suppliers = Supplier::select('id', 'supplier_name')
            ->where('active', true)
            ->orderBy('supplier_name')
            ->get();

        $currencies = Currency::select('id', 'code', 'name', 'symbol')
            ->where('active', true)
            ->orderBy('code')
            ->get();

        return Inertia::render('PurchaseOrders/Create', [
            'suppliers' => $suppliers,
            'currencies' => $currencies,
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $order = $this->service->create($request->validated(), $request->user());

        return redirect()->route('purchase-orders.show', $order)
            ->with('success', 'Purchase order created.');
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder): InertiaResponse
    {
        $purchaseOrder->load([
            'supplier', 'store', 'items.product', 'items.currency',
            'submittedByUser', 'resolvedByUser', 'createdBy',
        ]);

        // Get stores for accept dialog
        $stores = $this->getStoresForAccept($request->user());

        return Inertia::render('PurchaseOrders/View', [
            'order' => PurchaseOrderResource::make($purchaseOrder)->resolve(),
            'stores' => $stores,
        ]);
    }

    public function edit(Request $request, PurchaseOrder $purchaseOrder): InertiaResponse
    {
        abort_unless($purchaseOrder->status === PurchaseOrderStatus::DRAFT, 422, 'Only draft orders can be edited.');

        $purchaseOrder->load(['supplier', 'store', 'items.product', 'items.currency']);

        $suppliers = Supplier::select('id', 'supplier_name')
            ->where('active', true)
            ->orderBy('supplier_name')
            ->get();

        $currencies = Currency::select('id', 'code', 'name', 'symbol')
            ->where('active', true)
            ->orderBy('code')
            ->get();

        return Inertia::render('PurchaseOrders/Edit', [
            'order' => PurchaseOrderResource::make($purchaseOrder)->resolve(),
            'suppliers' => $suppliers,
            'currencies' => $currencies,
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->service->update($purchaseOrder, $request->validated(), $request->user());

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order updated.');
    }

    public function submit(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->service->submit($purchaseOrder, $request->user());

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order submitted.');
    }

    public function accept(AcceptPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $user = $request->user();
        $storeId = $request->validated('store_id');

        // Check store-level permission for accepting
        if (! $user->isAdmin()) {
            $employee = $user->employee;
            abort_unless(
                $employee?->hasStorePermission($storeId, StorePermissions::ACCEPT_PURCHASE_ORDER),
                403,
                'You do not have permission to accept purchase orders at this store.'
            );
        }

        $purchaseOrder->load('items');
        $this->service->accept($purchaseOrder, $storeId, $request->validated('items'), $user);

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order accepted.');
    }

    public function reject(RejectPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->service->reject($purchaseOrder, $request->validated('rejection_reason'), $request->user());

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order rejected.');
    }

    public function revert(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            $employee = $user->employee;
            abort_unless(
                $employee?->hasStorePermission($purchaseOrder->store_id, StorePermissions::ACCEPT_PURCHASE_ORDER),
                403,
                'You do not have permission to revert purchase orders at this store.'
            );
        }

        $purchaseOrder->load('items');
        $this->service->revert($purchaseOrder, $user);

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order reverted to submitted.');
    }

    public function destroy(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->service->delete($purchaseOrder);

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order deleted.');
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $search = $request->query('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $products = Product::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->search($search)
            ->limit(20)
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'product_name' => $product->product_name,
                'product_number' => $product->product_number,
                'variant_name' => $product->variant_name,
                'barcode' => $product->barcode,
                'image_url' => $product->image_path ? route('products.image', $product->id) : null,
            ]);

        return response()->json($products);
    }

    protected function getStoresForAccept(mixed $user): array
    {
        if ($user->isAdmin()) {
            return Store::select('id', 'store_name', 'store_code')
                ->orderBy('store_name')
                ->get()
                ->toArray();
        }

        $employee = $user->employee;
        if (! $employee) {
            return [];
        }

        return $employee->employeeStores()
            ->where('active', true)
            ->get()
            ->filter(fn ($es) => $es->hasPermission(StorePermissions::ACCEPT_PURCHASE_ORDER))
            ->map(fn ($es) => $es->store)
            ->filter()
            ->values()
            ->map(fn ($store) => [
                'id' => $store->id,
                'store_name' => $store->store_name,
                'store_code' => $store->store_code,
            ])
            ->toArray();
    }
}

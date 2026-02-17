<?php

namespace App\Http\Controllers;

use App\Constants\StorePermissions;
use App\Enums\DeliveryOrderStatus;
use App\Http\Requests\ApproveDeliveryOrderRequest;
use App\Http\Requests\RejectDeliveryOrderRequest;
use App\Http\Requests\StoreDeliveryOrderRequest;
use App\Http\Requests\UpdateDeliveryOrderRequest;
use App\Http\Resources\DeliveryOrderResource;
use App\Models\DeliveryOrder;
use App\Models\Product;
use App\Models\Store;
use App\Services\DeliveryOrderService;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DeliveryOrderController extends Controller
{
    public function __construct(
        private readonly DeliveryOrderService $service,
        private readonly PermissionService $permissionService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $query = DeliveryOrder::with(['storeFrom', 'storeTo', 'createdBy'])
            ->withCount('items');

        // Filter by accessible stores for non-admin users
        if (! $user->isAdmin()) {
            $accessibleStoreIds = $this->permissionService->getAccessibleStoreIds($user);
            $query->where(function ($q) use ($accessibleStoreIds) {
                $q->whereIn('store_id_from', $accessibleStoreIds)
                    ->orWhereIn('store_id_to', $accessibleStoreIds);
            });
        }

        // Filters
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

        $stores = Store::select('id', 'store_name', 'store_code')
            ->orderBy('store_name')
            ->get();

        return Inertia::render('DeliveryOrders/Index', [
            'orders' => DeliveryOrderResource::collection($orders),
            'stores' => $stores,
            'filters' => $request->only(['store_id', 'status', 'search', 'start_date', 'end_date']),
            'statusOptions' => DeliveryOrderStatus::options(),
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $user = $request->user();
        $stores = $this->getStoresWithPermission($user, StorePermissions::ADD_DELIVERY_ORDER);

        return Inertia::render('DeliveryOrders/Create', [
            'stores' => $stores,
        ]);
    }

    public function store(StoreDeliveryOrderRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Verify store permission
        if (! $user->isAdmin()) {
            $employee = $user->employee;
            abort_unless(
                $employee?->hasStorePermission($request->store_id_from, StorePermissions::ADD_DELIVERY_ORDER),
                403,
                'You do not have permission to create delivery orders from this store.'
            );
        }

        $order = $this->service->create($request->validated(), $user);

        return redirect()->route('delivery-orders.show', $order)
            ->with('success', 'Delivery order created.');
    }

    public function show(Request $request, DeliveryOrder $deliveryOrder): InertiaResponse
    {
        $this->authorizeAccess($request->user(), $deliveryOrder);

        $deliveryOrder->load([
            'storeFrom', 'storeTo', 'items.product',
            'submittedByUser', 'resolvedByUser', 'createdBy',
        ]);

        return Inertia::render('DeliveryOrders/View', [
            'order' => DeliveryOrderResource::make($deliveryOrder)->resolve(),
        ]);
    }

    public function edit(Request $request, DeliveryOrder $deliveryOrder): InertiaResponse
    {
        $this->authorizeAccess($request->user(), $deliveryOrder);
        abort_unless($deliveryOrder->status === DeliveryOrderStatus::DRAFT, 422, 'Only draft orders can be edited.');

        $user = $request->user();
        $stores = $this->getStoresWithPermission($user, StorePermissions::ADD_DELIVERY_ORDER);

        $deliveryOrder->load(['storeFrom', 'storeTo', 'items.product']);

        return Inertia::render('DeliveryOrders/Edit', [
            'order' => DeliveryOrderResource::make($deliveryOrder)->resolve(),
            'stores' => $stores,
        ]);
    }

    public function update(UpdateDeliveryOrderRequest $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $this->authorizeAccess($request->user(), $deliveryOrder);

        $this->service->update($deliveryOrder, $request->validated(), $request->user());

        return redirect()->route('delivery-orders.show', $deliveryOrder)
            ->with('success', 'Delivery order updated.');
    }

    public function submit(Request $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $this->authorizeAccess($request->user(), $deliveryOrder);

        $this->service->submit($deliveryOrder, $request->user());

        return redirect()->route('delivery-orders.show', $deliveryOrder)
            ->with('success', 'Delivery order submitted for approval.');
    }

    public function approve(ApproveDeliveryOrderRequest $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $user = $request->user();

        // Check approve permission on the destination store
        if (! $user->isAdmin()) {
            $employee = $user->employee;
            abort_unless(
                $employee?->hasStorePermission($deliveryOrder->store_id_to, StorePermissions::APPROVE_DELIVERY_ORDER),
                403,
                'You do not have permission to approve delivery orders for this store.'
            );
        }

        $deliveryOrder->load('items');
        $this->service->approve($deliveryOrder, $request->validated('items'), $user);

        return redirect()->route('delivery-orders.show', $deliveryOrder)
            ->with('success', 'Delivery order approved.');
    }

    public function reject(RejectDeliveryOrderRequest $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            $employee = $user->employee;
            abort_unless(
                $employee?->hasStorePermission($deliveryOrder->store_id_to, StorePermissions::APPROVE_DELIVERY_ORDER),
                403,
                'You do not have permission to reject delivery orders for this store.'
            );
        }

        $this->service->reject($deliveryOrder, $request->validated('rejection_reason'), $user);

        return redirect()->route('delivery-orders.show', $deliveryOrder)
            ->with('success', 'Delivery order rejected.');
    }

    public function revert(Request $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            $employee = $user->employee;
            abort_unless(
                $employee?->hasStorePermission($deliveryOrder->store_id_to, StorePermissions::APPROVE_DELIVERY_ORDER),
                403,
                'You do not have permission to revert delivery orders for this store.'
            );
        }

        $deliveryOrder->load('items');
        $this->service->revert($deliveryOrder, $user);

        return redirect()->route('delivery-orders.show', $deliveryOrder)
            ->with('success', 'Delivery order reverted to submitted.');
    }

    public function destroy(Request $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $this->authorizeAccess($request->user(), $deliveryOrder);

        $this->service->delete($deliveryOrder);

        return redirect()->route('delivery-orders.index')
            ->with('success', 'Delivery order deleted.');
    }

    public function searchProducts(Request $request, DeliveryOrder $deliveryOrder): JsonResponse
    {
        $search = $request->query('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $existingIds = $deliveryOrder->items()->pluck('product_id')->toArray();

        $products = Product::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereHas('productStores', function ($q) use ($deliveryOrder) {
                $q->where('store_id', $deliveryOrder->store_id_from);
            })
            ->whereNotIn('id', $existingIds)
            ->search($search)
            ->limit(50)
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

    protected function authorizeAccess(mixed $user, DeliveryOrder $order): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $accessibleStoreIds = $this->permissionService->getAccessibleStoreIds($user);
        $canAccess = in_array($order->store_id_from, $accessibleStoreIds) || in_array($order->store_id_to, $accessibleStoreIds);

        abort_unless($canAccess, 403, 'You do not have access to this delivery order.');
    }

    protected function getStoresWithPermission(mixed $user, string $permission): array
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
            ->filter(fn ($es) => $es->hasPermission($permission))
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

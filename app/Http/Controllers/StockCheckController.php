<?php

namespace App\Http\Controllers;

use App\Constants\StoreAccessPermissions;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockCheckController extends Controller
{
    public function index(Request $request, PermissionService $permissionService): InertiaResponse
    {
        $user = $request->user();
        $search = $request->query('search', '');
        $categoryId = $request->query('category_id', '');
        $subcategoryId = $request->query('subcategory_id', '');
        $storeId = $request->query('store_id', '');
        $perPage = min(max($request->integer('per_page', 50), 10), 100);
        $page = $request->integer('page', 1);

        // Get user's accessible store IDs
        $accessibleStoreIds = $permissionService->getAccessibleStoreIds($user);

        // Build stock count permission map per store
        $storeStockCountPermissions = [];
        foreach ($accessibleStoreIds as $sid) {
            $storeStockCountPermissions[$sid] = $permissionService->canAccessStore(
                $user,
                $sid,
                StoreAccessPermissions::STORE_VIEW_STOCK_COUNT
            );
        }

        // Search-first design: only query when filters are present
        $products = null;
        $hasFilters = $search || $categoryId || $subcategoryId || $storeId;

        if ($hasFilters && ! empty($accessibleStoreIds)) {
            // Determine which store IDs to filter product_stores by
            $filterStoreIds = $accessibleStoreIds;
            if ($storeId && in_array((int) $storeId, $accessibleStoreIds)) {
                $filterStoreIds = [(int) $storeId];
            }

            $query = Product::with([
                'brand:id,brand_name',
                'category:id,category_name',
                'subcategory:id,subcategory_name',
                'productStores' => fn ($q) => $q->whereIn('store_id', $accessibleStoreIds)->with('store:id,store_name,store_code'),
            ])
                ->active()
                ->whereHas('productStores', function ($q) use ($filterStoreIds) {
                    $q->whereIn('store_id', $filterStoreIds);
                });

            if ($search) {
                $query->search($search);
            }

            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            if ($subcategoryId) {
                $query->where('subcategory_id', $subcategoryId);
            }

            $paginated = $query
                ->orderBy('product_name')
                ->paginate($perPage, ['*'], 'page', $page)
                ->withQueryString();

            // Transform results with stock visibility
            $transformedData = collect($paginated->items())->map(function (Product $product) use ($storeStockCountPermissions) {
                $stores = $product->productStores->map(function ($ps) use ($storeStockCountPermissions) {
                    $canViewCount = $storeStockCountPermissions[$ps->store_id] ?? false;

                    return [
                        'store_id' => $ps->store_id,
                        'store_name' => $ps->store?->store_name,
                        'store_code' => $ps->store?->store_code,
                        'quantity' => $canViewCount ? $ps->quantity : null,
                        'in_stock' => $ps->quantity > 0,
                    ];
                });

                return [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'product_number' => $product->product_number,
                    'barcode' => $product->barcode,
                    'variant_name' => $product->variant_name,
                    'parent_product_id' => $product->parent_product_id,
                    'brand_name' => $product->brand?->brand_name,
                    'category_name' => $product->category?->category_name,
                    'subcategory_name' => $product->subcategory?->subcategory_name,
                    'image_url' => $product->image_path ? route('stock-check.product-image', $product->id) : null,
                    'stores' => $stores,
                ];
            });

            $products = [
                'data' => $transformedData->values()->toArray(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ];
        }

        return Inertia::render('StockCheck/Index', [
            'products' => $products,
            'categories' => Category::where('is_active', true)
                ->with(['subcategories' => fn ($q) => $q->where('is_active', true)->orderBy('subcategory_name')])
                ->orderBy('category_name')
                ->get(['id', 'category_name', 'category_code']),
            'stores' => Store::whereIn('id', $accessibleStoreIds)
                ->where('active', true)
                ->orderBy('store_name')
                ->get(['id', 'store_name', 'store_code']),
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'subcategory_id' => $subcategoryId,
                'store_id' => $storeId,
                'per_page' => $perPage,
            ],
            'storeStockCountPermissions' => $storeStockCountPermissions,
        ]);
    }

    public function showProductImage(Product $product): StreamedResponse
    {
        if (! $product->image_path || ! Storage::disk('private')->exists($product->image_path)) {
            abort(404);
        }

        return Storage::disk('private')->response($product->image_path);
    }
}

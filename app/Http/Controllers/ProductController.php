<?php

namespace App\Http\Controllers;

use App\Enums\WeightUnit;
use App\Http\Requests\BatchUpdateProductsRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\ProductStore;
use App\Models\ProductStorePrice;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Tag;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    use RespondsWithInertiaOrJson;

    public function index(Request $request, PermissionService $permissionService): InertiaResponse|JsonResponse
    {
        $search = $request->query('search', '');
        $status = $request->query('status', '');
        $brandId = $request->query('brand_id', '');
        $categoryId = $request->query('category_id', '');
        $supplierId = $request->query('supplier_id', '');
        $storeIds = $request->query('store_ids', []);
        $showDeleted = $request->boolean('show_deleted', false);
        $excludeVariants = $request->boolean('exclude_variants', false);
        $perPage = min(max($request->integer('per_page', 15), 10), 100);

        // Normalize store_ids to array of integers
        if (is_string($storeIds)) {
            $storeIds = array_filter(array_map('intval', explode(',', $storeIds)));
        } elseif (is_array($storeIds)) {
            $storeIds = array_filter(array_map('intval', $storeIds));
        } else {
            $storeIds = [];
        }

        // Get employee's accessible store IDs
        $accessibleStoreIds = $permissionService->getAccessibleStoreIds($request->user());

        $query = Product::with([
            'brand:id,brand_name,brand_code',
            'category:id,category_name,category_code',
            'subcategory:id,subcategory_name,subcategory_code',
            'supplier:id,supplier_name',
            'prices.currency',
            'tags:id,name',
        ]);

        // Only show products assigned to employee's accessible stores
        if (! empty($accessibleStoreIds)) {
            $query->whereHas('productStores', function ($q) use ($accessibleStoreIds) {
                $q->whereIn('store_id', $accessibleStoreIds);
            });
        } else {
            // No accessible stores means no products visible
            $query->whereRaw('1 = 0');
        }

        // Apply store filter (must be within accessible stores)
        $filteredStoreIds = array_intersect($storeIds, $accessibleStoreIds);
        if (! empty($filteredStoreIds)) {
            $query->whereHas('productStores', function ($q) use ($filteredStoreIds) {
                $q->whereIn('store_id', $filteredStoreIds);
            });
        }

        if ($showDeleted) {
            $query->withTrashed();
        }

        if ($search) {
            $query->search($search);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if ($excludeVariants) {
            $query->excludeVariants();
        }

        $products = $query
            ->withCount('variants')
            ->orderBy('product_name')
            ->paginate($perPage)
            ->withQueryString();

        if ($this->wantsJson($request)) {
            return ProductResource::collection($products)->response();
        }

        $transformedProducts = [
            'data' => ProductResource::collection($products->items())->resolve(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
        ];

        return Inertia::render('Products/Index', [
            'products' => $transformedProducts,
            'brands' => Brand::active()->orderBy('brand_name')->get(['id', 'brand_name', 'brand_code']),
            'categories' => Category::where('is_active', true)
                ->with(['subcategories' => fn ($q) => $q->where('is_active', true)->orderBy('subcategory_name')])
                ->orderBy('category_name')
                ->get(['id', 'category_name', 'category_code']),
            'suppliers' => Supplier::where('active', true)->orderBy('supplier_name')->get(['id', 'supplier_name']),
            'currencies' => Currency::active()->orderBy('code')->get(['id', 'code', 'name', 'symbol', 'decimal_places']),
            'stores' => Store::whereIn('id', $accessibleStoreIds)
                ->where('active', true)
                ->orderBy('store_name')
                ->get(['id', 'store_name', 'store_code']),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'brand_id' => $brandId,
                'category_id' => $categoryId,
                'supplier_id' => $supplierId,
                'store_ids' => $storeIds,
                'show_deleted' => $showDeleted,
                'exclude_variants' => $excludeVariants,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Products/Form', [
            'product' => null,
            'parentProduct' => null,
            'brands' => Brand::active()->orderBy('brand_name')->get(['id', 'brand_name', 'brand_code']),
            'categories' => Category::where('is_active', true)
                ->with(['subcategories' => fn ($q) => $q->where('is_active', true)->orderBy('subcategory_name')])
                ->orderBy('category_name')
                ->get(['id', 'category_name', 'category_code']),
            'suppliers' => Supplier::where('active', true)->orderBy('supplier_name')->get(['id', 'supplier_name']),
            'currencies' => Currency::active()->orderBy('code')->get(['id', 'code', 'name', 'symbol', 'decimal_places']),
            'stores' => Store::where('active', true)
                ->with(['storeCurrencies.currency'])
                ->orderBy('store_name')
                ->get(['id', 'store_name', 'store_code']),
            'weightUnits' => WeightUnit::options(),
        ]);
    }

    public function createVariant(Product $product): InertiaResponse
    {
        // Only non-variants can have variants created from them
        if ($product->isVariant()) {
            abort(422, 'Cannot create a variant of a variant.');
        }

        $product->load([
            'brand',
            'category',
            'subcategory',
            'supplier',
        ]);

        return Inertia::render('Products/Form', [
            'product' => null,
            'parentProduct' => (new ProductResource($product))->resolve(),
            'brands' => Brand::active()->orderBy('brand_name')->get(['id', 'brand_name', 'brand_code']),
            'categories' => Category::where('is_active', true)
                ->with(['subcategories' => fn ($q) => $q->where('is_active', true)->orderBy('subcategory_name')])
                ->orderBy('category_name')
                ->get(['id', 'category_name', 'category_code']),
            'suppliers' => Supplier::where('active', true)->orderBy('supplier_name')->get(['id', 'supplier_name']),
            'currencies' => Currency::active()->orderBy('code')->get(['id', 'code', 'name', 'symbol', 'decimal_places']),
            'stores' => Store::where('active', true)
                ->with(['storeCurrencies.currency'])
                ->orderBy('store_name')
                ->get(['id', 'store_name', 'store_code']),
            'weightUnits' => WeightUnit::options(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse|JsonResponse
    {
        return DB::transaction(function () use ($request) {
            // Create product
            $data = collect($request->validated())->except(['image', 'prices', 'stores', 'tags'])->toArray();
            $product = Product::create($data);

            // Sync tags
            if ($request->has('tags')) {
                $product->syncTagsByName($request->input('tags', []));
            }

            // Handle image upload if provided
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->store("product-images/{$product->id}", 'private');
                $product->update([
                    'image_path' => $path,
                    'image_filename' => $file->getClientOriginalName(),
                    'image_mime_type' => $file->getMimeType(),
                ]);
            }

            // Create base prices
            if ($request->has('prices')) {
                foreach ($request->input('prices') as $priceData) {
                    ProductPrice::create([
                        'product_id' => $product->id,
                        'currency_id' => $priceData['currency_id'],
                        'cost_price' => $priceData['cost_price'] ?? null,
                        'unit_price' => $priceData['unit_price'] ?? null,
                    ]);
                }
            }

            // Create store assignments
            if ($request->has('stores')) {
                foreach ($request->input('stores') as $storeData) {
                    $productStore = ProductStore::create([
                        'product_id' => $product->id,
                        'store_id' => $storeData['store_id'],
                        'quantity' => $storeData['quantity'] ?? 0,
                        'is_active' => $storeData['is_active'] ?? true,
                    ]);

                    // Create store-specific prices
                    if (isset($storeData['prices'])) {
                        foreach ($storeData['prices'] as $priceData) {
                            ProductStorePrice::create([
                                'product_store_id' => $productStore->id,
                                'currency_id' => $priceData['currency_id'],
                                'cost_price' => $priceData['cost_price'] ?? null,
                                'unit_price' => $priceData['unit_price'] ?? null,
                            ]);
                        }
                    }
                }
            }

            return $this->respondWithCreated(
                $request,
                'products.index',
                [],
                'Product created successfully.',
                new ProductResource($product->fresh([
                    'brand',
                    'category',
                    'subcategory',
                    'supplier',
                    'prices.currency',
                    'productStores.store',
                    'productStores.storePrices.currency',
                    'tags:id,name',
                ]))
            );
        });
    }

    public function show(Request $request, Product $product, PermissionService $permissionService): InertiaResponse|JsonResponse
    {
        // Verify product is in accessible stores
        $accessibleStoreIds = $permissionService->getAccessibleStoreIds($request->user());
        $productStoreIds = $product->productStores()->pluck('store_id')->toArray();

        if (empty(array_intersect($accessibleStoreIds, $productStoreIds))) {
            abort(403, 'You do not have access to this product.');
        }

        $product->load([
            'brand',
            'category',
            'subcategory',
            'supplier',
            'prices.currency',
            'productStores.store',
            'productStores.storePrices.currency',
            'tags:id,name',
            'images',
            'parent.brand',
            'variants' => fn ($q) => $q->with(['brand', 'prices.currency'])->orderBy('variant_name'),
            'createdBy:id,name',
            'updatedBy:id,name',
            'previousUpdatedBy:id,name',
        ]);

        $product->loadCount('variants');

        if ($this->wantsJson($request)) {
            return response()->json([
                'data' => (new ProductResource($product))->resolve(),
            ]);
        }

        return Inertia::render('Products/View', [
            'product' => (new ProductResource($product))->resolve(),
        ]);
    }

    public function variants(Request $request, Product $product): JsonResponse
    {
        // Return the variants for this product
        $variants = $product->variants()
            ->with(['brand:id,brand_name', 'prices.currency'])
            ->orderBy('variant_name')
            ->get();

        return response()->json([
            'data' => ProductResource::collection($variants)->resolve(),
        ]);
    }

    public function edit(Request $request, Product $product, PermissionService $permissionService): InertiaResponse
    {
        // Verify product is in accessible stores
        $accessibleStoreIds = $permissionService->getAccessibleStoreIds($request->user());
        $productStoreIds = $product->productStores()->pluck('store_id')->toArray();

        if (empty(array_intersect($accessibleStoreIds, $productStoreIds))) {
            abort(403, 'You do not have access to this product.');
        }

        $product->load([
            'brand',
            'category',
            'subcategory',
            'supplier',
            'prices.currency',
            'productStores.store',
            'productStores.storePrices.currency',
            'tags:id,name',
            'images',
        ]);

        return Inertia::render('Products/Form', [
            'product' => (new ProductResource($product))->resolve(),
            'brands' => Brand::active()->orderBy('brand_name')->get(['id', 'brand_name', 'brand_code']),
            'categories' => Category::where('is_active', true)
                ->with(['subcategories' => fn ($q) => $q->where('is_active', true)->orderBy('subcategory_name')])
                ->orderBy('category_name')
                ->get(['id', 'category_name', 'category_code']),
            'suppliers' => Supplier::where('active', true)->orderBy('supplier_name')->get(['id', 'supplier_name']),
            'currencies' => Currency::active()->orderBy('code')->get(['id', 'code', 'name', 'symbol', 'decimal_places']),
            'stores' => Store::where('active', true)
                ->with(['storeCurrencies.currency'])
                ->orderBy('store_name')
                ->get(['id', 'store_name', 'store_code']),
            'weightUnits' => WeightUnit::options(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse|JsonResponse
    {
        return DB::transaction(function () use ($request, $product) {
            // Update product
            $data = collect($request->validated())->except(['prices', 'stores', 'tags'])->toArray();
            $product->update($data);

            // Sync tags
            if ($request->has('tags')) {
                $product->syncTagsByName($request->input('tags', []));
            }

            // Sync base prices
            if ($request->has('prices')) {
                $product->prices()->delete();
                foreach ($request->input('prices') as $priceData) {
                    ProductPrice::create([
                        'product_id' => $product->id,
                        'currency_id' => $priceData['currency_id'],
                        'cost_price' => $priceData['cost_price'] ?? null,
                        'unit_price' => $priceData['unit_price'] ?? null,
                    ]);
                }
            }

            // Sync store assignments
            if ($request->has('stores')) {
                // Get existing store IDs
                $existingStoreIds = $product->productStores()->pluck('store_id')->toArray();
                $newStoreIds = collect($request->input('stores'))->pluck('store_id')->toArray();

                // Delete removed store assignments
                $product->productStores()
                    ->whereNotIn('store_id', $newStoreIds)
                    ->delete();

                foreach ($request->input('stores') as $storeData) {
                    $productStore = ProductStore::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'store_id' => $storeData['store_id'],
                        ],
                        [
                            'quantity' => $storeData['quantity'] ?? 0,
                            'is_active' => $storeData['is_active'] ?? true,
                        ]
                    );

                    // Sync store-specific prices
                    $productStore->storePrices()->delete();
                    if (isset($storeData['prices'])) {
                        foreach ($storeData['prices'] as $priceData) {
                            ProductStorePrice::create([
                                'product_store_id' => $productStore->id,
                                'currency_id' => $priceData['currency_id'],
                                'cost_price' => $priceData['cost_price'] ?? null,
                                'unit_price' => $priceData['unit_price'] ?? null,
                            ]);
                        }
                    }
                }
            }

            return $this->respondWithSuccess(
                $request,
                'products.show',
                ['product' => $product->id],
                'Product updated successfully.',
                (new ProductResource($product->fresh([
                    'brand',
                    'category',
                    'subcategory',
                    'supplier',
                    'prices.currency',
                    'productStores.store',
                    'productStores.storePrices.currency',
                    'tags:id,name',
                ])))->resolve()
            );
        });
    }

    public function destroy(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        if ($product->variants()->exists()) {
            abort(422, 'Cannot delete a parent product while it has active variants. Delete all variants first.');
        }

        // Delete image if exists
        if ($product->image_path) {
            Storage::disk('private')->delete($product->image_path);
        }

        $product->delete();

        return $this->respondWithDeleted(
            $request,
            'products.index',
            [],
            'Product deleted successfully.'
        );
    }

    public function restore(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $product->restore();

        return $this->respondWithSuccess(
            $request,
            'products.index',
            ['show_deleted' => true],
            'Product restored successfully.',
            (new ProductResource($product->fresh([
                'brand',
                'category',
                'subcategory',
                'supplier',
                'prices.currency',
                'tags:id,name',
            ])))->resolve()
        );
    }

    public function showImage(Product $product): StreamedResponse
    {
        if (! $product->image_path || ! Storage::disk('private')->exists($product->image_path)) {
            abort(404);
        }

        return Storage::disk('private')->response($product->image_path);
    }

    public function uploadImage(Product $product, Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        // Delete old image if exists
        if ($product->image_path) {
            Storage::disk('private')->delete($product->image_path);
        }

        // Store new image
        $file = $request->file('image');
        $path = $file->store("product-images/{$product->id}", 'private');

        $product->update([
            'image_path' => $path,
            'image_filename' => $file->getClientOriginalName(),
            'image_mime_type' => $file->getMimeType(),
        ]);

        return $this->respondWithSuccess(
            $request,
            'products.edit',
            ['product' => $product->id],
            'Image uploaded successfully.',
            ['image_url' => route('products.image', $product->id)]
        );
    }

    public function deleteImage(Product $product, Request $request): RedirectResponse|JsonResponse
    {
        if ($product->image_path) {
            Storage::disk('private')->delete($product->image_path);
            $product->update([
                'image_path' => null,
                'image_filename' => null,
                'image_mime_type' => null,
            ]);
        }

        return $this->respondWithSuccess(
            $request,
            'products.edit',
            ['product' => $product->id],
            'Image removed successfully.',
            ['image_url' => null]
        );
    }

    public function showSupplementaryImage(Product $product, ProductImage $image): StreamedResponse
    {
        // Verify the image belongs to this product
        if ($image->product_id !== $product->id) {
            abort(404);
        }

        if (! Storage::disk('private')->exists($image->image_path)) {
            abort(404);
        }

        return Storage::disk('private')->response($image->image_path);
    }

    public function uploadSupplementaryImage(Product $product, Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        // Get current max sort_order
        $maxSortOrder = $product->images()->max('sort_order') ?? -1;

        // Store the image
        $file = $request->file('image');
        $path = $file->store("product-images/{$product->id}/supplementary", 'private');

        $image = $product->images()->create([
            'image_path' => $path,
            'image_filename' => $file->getClientOriginalName(),
            'image_mime_type' => $file->getMimeType(),
            'sort_order' => $maxSortOrder + 1,
        ]);

        return response()->json([
            'message' => 'Image uploaded successfully.',
            'image' => [
                'id' => $image->id,
                'image_url' => route('products.supplementary-image', [$product->id, $image->id]),
                'image_filename' => $image->image_filename,
                'sort_order' => $image->sort_order,
            ],
        ]);
    }

    public function deleteSupplementaryImage(Product $product, ProductImage $image, Request $request): JsonResponse
    {
        // Verify the image belongs to this product
        if ($image->product_id !== $product->id) {
            abort(404);
        }

        // Delete the file
        if (Storage::disk('private')->exists($image->image_path)) {
            Storage::disk('private')->delete($image->image_path);
        }

        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully.',
        ]);
    }

    public function reorderImages(Product $product, Request $request): JsonResponse
    {
        $request->validate([
            'image_ids' => ['required', 'array'],
            'image_ids.*' => ['required', 'integer', 'exists:product_images,id'],
        ]);

        $imageIds = $request->input('image_ids');

        // Verify all images belong to this product
        $productImageIds = $product->images()->pluck('id')->toArray();
        foreach ($imageIds as $imageId) {
            if (! in_array($imageId, $productImageIds)) {
                return response()->json([
                    'message' => 'Image does not belong to this product.',
                ], 422);
            }
        }

        // Update sort orders
        foreach ($imageIds as $index => $imageId) {
            ProductImage::where('id', $imageId)->update(['sort_order' => $index]);
        }

        return response()->json([
            'message' => 'Images reordered successfully.',
        ]);
    }

    public function promoteToCover(Product $product, ProductImage $image, Request $request): JsonResponse
    {
        // Verify the image belongs to this product
        if ($image->product_id !== $product->id) {
            abort(404);
        }

        return DB::transaction(function () use ($product, $image) {
            // Save current cover image data
            $oldCoverPath = $product->image_path;
            $oldCoverFilename = $product->image_filename;
            $oldCoverMimeType = $product->image_mime_type;

            // Update product with supplementary image's data (becomes new cover)
            $product->update([
                'image_path' => $image->image_path,
                'image_filename' => $image->image_filename,
                'image_mime_type' => $image->image_mime_type,
            ]);

            // If old cover existed, update the supplementary image record with old cover data
            if ($oldCoverPath) {
                $image->update([
                    'image_path' => $oldCoverPath,
                    'image_filename' => $oldCoverFilename,
                    'image_mime_type' => $oldCoverMimeType,
                ]);
            } else {
                // If no old cover, delete the supplementary image record
                $image->delete();
            }

            // Reload images to get updated list
            $product->load('images');

            return response()->json([
                'message' => 'Image promoted to cover successfully.',
                'cover_url' => route('products.image', $product->id),
                'images' => $product->images->map(fn ($img) => [
                    'id' => $img->id,
                    'image_url' => route('products.supplementary-image', [$product->id, $img->id]),
                    'image_filename' => $img->image_filename,
                    'sort_order' => $img->sort_order,
                ])->values()->toArray(),
            ]);
        });
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        $limit = min((int) $request->query('limit', 20), 50);
        $storeId = $request->query('store_id');

        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $productsQuery = Product::query()
            ->search($query)
            ->orderBy('product_name')
            ->limit($limit);

        if ($storeId) {
            $productsQuery->whereHas('productStores', fn ($q) => $q->where('store_id', $storeId));
        }

        $products = $productsQuery->get(['id', 'product_name', 'product_number', 'barcode', 'brand_id', 'image_path']);

        $brandIds = $products->pluck('brand_id')->filter()->unique()->values();
        $brands = Brand::whereIn('id', $brandIds)->pluck('brand_name', 'id');

        // Load store assignments for all found products
        $productIds = $products->pluck('id');
        $storeIdsByProduct = \App\Models\ProductStore::whereIn('product_id', $productIds)
            ->get(['product_id', 'store_id'])
            ->groupBy('product_id')
            ->map(fn ($items) => $items->pluck('store_id')->values()->toArray());

        $results = $products->map(fn (Product $product) => [
            'id' => $product->id,
            'product_name' => $product->product_name,
            'product_number' => $product->product_number,
            'barcode' => $product->barcode,
            'brand_name' => $brands[$product->brand_id] ?? null,
            'image_url' => $product->image_path ? route('products.image', $product->id) : null,
            'store_ids' => $storeIdsByProduct[$product->id] ?? [],
        ]);

        return response()->json(['data' => $results]);
    }

    /**
     * Search for products that can be linked as variants.
     * Excludes products that are already variants and the current product.
     */
    public function searchLinkable(Request $request, Product $product): JsonResponse
    {
        $query = $request->query('q', '');
        $limit = min((int) $request->query('limit', 20), 50);

        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $products = Product::query()
            ->search($query)
            ->whereNull('parent_product_id') // Only standalone products
            ->where('id', '!=', $product->id) // Exclude current product
            ->doesntHave('variants') // Exclude products that have variants (they are parents)
            ->orderBy('product_name')
            ->limit($limit)
            ->get(['id', 'product_name', 'product_number', 'barcode', 'brand_id', 'image_path']);

        $brandIds = $products->pluck('brand_id')->filter()->unique()->values();
        $brands = Brand::whereIn('id', $brandIds)->pluck('brand_name', 'id');

        $results = $products->map(fn (Product $p) => [
            'id' => $p->id,
            'product_name' => $p->product_name,
            'product_number' => $p->product_number,
            'barcode' => $p->barcode,
            'brand_name' => $brands[$p->brand_id] ?? null,
            'image_url' => $p->image_path ? route('products.image', $p->id) : null,
        ]);

        return response()->json(['data' => $results]);
    }

    /**
     * Link an existing product as a variant of this product.
     */
    public function linkAsVariant(Request $request, Product $product): JsonResponse
    {
        // Parent cannot be a variant itself
        if ($product->isVariant()) {
            return response()->json(['message' => 'Cannot add variants to a variant product.'], 422);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_name' => 'required|string|max:255',
        ]);

        $child = Product::findOrFail($validated['product_id']);

        // Child cannot already be a variant
        if ($child->isVariant()) {
            return response()->json(['message' => 'This product is already a variant.'], 422);
        }

        // Child cannot have variants (would become orphaned)
        if ($child->hasVariants()) {
            return response()->json(['message' => 'Cannot link a product that has variants.'], 422);
        }

        // Cannot link to itself
        if ($child->id === $product->id) {
            return response()->json(['message' => 'Cannot link a product to itself.'], 422);
        }

        $child->parent_product_id = $product->id;
        $child->variant_name = $validated['variant_name'];
        $child->save();

        return response()->json([
            'message' => 'Product linked as variant successfully.',
            'variant' => [
                'id' => $child->id,
                'variant_name' => $child->variant_name,
                'product_number' => $child->product_number,
                'is_active' => $child->is_active,
            ],
        ]);
    }

    public function preview(Request $request, Product $product): JsonResponse
    {
        $product->load([
            'brand:id,brand_name,brand_code',
            'category:id,category_name,category_code',
            'subcategory:id,subcategory_name,subcategory_code',
            'supplier:id,supplier_name',
            'prices.currency',
            'productStores.store:id,store_name,store_code',
            'tags:id,name',
            'parent:id,product_name,product_number',
            'variants' => fn ($q) => $q->orderBy('variant_name'),
        ]);

        return response()->json([
            'data' => (new ProductResource($product))->resolve(),
        ]);
    }

    public function adjacentIds(Request $request, Product $product, PermissionService $permissionService): JsonResponse
    {
        $search = $request->query('search', '');
        $status = $request->query('status', '');
        $brandId = $request->query('brand_id', '');
        $categoryId = $request->query('category_id', '');
        $supplierId = $request->query('supplier_id', '');
        $storeIds = $request->query('store_ids', []);
        $showDeleted = $request->boolean('show_deleted', false);

        // Normalize store_ids to array of integers
        if (is_string($storeIds)) {
            $storeIds = array_filter(array_map('intval', explode(',', $storeIds)));
        } elseif (is_array($storeIds)) {
            $storeIds = array_filter(array_map('intval', $storeIds));
        } else {
            $storeIds = [];
        }

        // Get employee's accessible store IDs
        $accessibleStoreIds = $permissionService->getAccessibleStoreIds($request->user());

        // Build base query with same filters as index
        $query = Product::query();

        // Only show products assigned to employee's accessible stores
        if (! empty($accessibleStoreIds)) {
            $query->whereHas('productStores', function ($q) use ($accessibleStoreIds) {
                $q->whereIn('store_id', $accessibleStoreIds);
            });
        } else {
            $query->whereRaw('1 = 0');
        }

        // Apply store filter (must be within accessible stores)
        $filteredStoreIds = array_intersect($storeIds, $accessibleStoreIds);
        if (! empty($filteredStoreIds)) {
            $query->whereHas('productStores', function ($q) use ($filteredStoreIds) {
                $q->whereIn('store_id', $filteredStoreIds);
            });
        }

        if ($showDeleted) {
            $query->withTrashed();
        }

        if ($search) {
            $query->search($search);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        // Get all product IDs in order
        $orderedIds = (clone $query)
            ->orderBy('product_name')
            ->pluck('id')
            ->values()
            ->toArray();

        $total = count($orderedIds);
        $currentIndex = array_search($product->id, $orderedIds);

        if ($currentIndex === false) {
            return response()->json([
                'prev_id' => null,
                'next_id' => null,
                'position' => null,
                'total' => $total,
            ]);
        }

        $prevId = $currentIndex > 0 ? $orderedIds[$currentIndex - 1] : null;
        $nextId = $currentIndex < $total - 1 ? $orderedIds[$currentIndex + 1] : null;

        return response()->json([
            'prev_id' => $prevId,
            'next_id' => $nextId,
            'position' => $currentIndex + 1,
            'total' => $total,
        ]);
    }

    /**
     * Get all product IDs matching the current filters (for "select all" functionality).
     * Returns minimal data needed for selection: id, product_name, product_number, brand_name, image_url.
     * Excludes deleted products since they can't be batch edited.
     */
    public function getAllIds(Request $request, PermissionService $permissionService): JsonResponse
    {
        $search = $request->query('search', '');
        $status = $request->query('status', '');
        $brandId = $request->query('brand_id', '');
        $categoryId = $request->query('category_id', '');
        $supplierId = $request->query('supplier_id', '');
        $storeIds = $request->query('store_ids', []);

        // Normalize store_ids to array of integers
        if (is_string($storeIds)) {
            $storeIds = array_filter(array_map('intval', explode(',', $storeIds)));
        } elseif (is_array($storeIds)) {
            $storeIds = array_filter(array_map('intval', $storeIds));
        } else {
            $storeIds = [];
        }

        // Get employee's accessible store IDs
        $accessibleStoreIds = $permissionService->getAccessibleStoreIds($request->user());

        // Build query without deleted products (they can't be batch edited)
        $query = Product::query();

        // Only show products assigned to employee's accessible stores
        if (! empty($accessibleStoreIds)) {
            $query->whereHas('productStores', function ($q) use ($accessibleStoreIds) {
                $q->whereIn('store_id', $accessibleStoreIds);
            });
        } else {
            $query->whereRaw('1 = 0');
        }

        // Apply store filter (must be within accessible stores)
        $filteredStoreIds = array_intersect($storeIds, $accessibleStoreIds);
        if (! empty($filteredStoreIds)) {
            $query->whereHas('productStores', function ($q) use ($filteredStoreIds) {
                $q->whereIn('store_id', $filteredStoreIds);
            });
        }

        if ($search) {
            $query->search($search);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        // Get all products with minimal data
        $products = $query
            ->orderBy('product_name')
            ->get(['id', 'product_name', 'product_number', 'brand_id', 'image_path']);

        // Load brand names efficiently
        $brandIds = $products->pluck('brand_id')->filter()->unique()->values();
        $brands = Brand::whereIn('id', $brandIds)->pluck('brand_name', 'id');

        // Transform to minimal data structure
        $data = $products->map(fn (Product $product) => [
            'id' => $product->id,
            'product_name' => $product->product_name,
            'product_number' => $product->product_number,
            'brand_name' => $brands[$product->brand_id] ?? null,
            'image_url' => $product->image_path ? route('products.image', $product->id) : null,
        ])->values();

        return response()->json([
            'data' => $data,
            'total' => $data->count(),
        ]);
    }

    public function batchUpdate(BatchUpdateProductsRequest $request): RedirectResponse|JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $productIds = $request->input('product_ids');
            $products = Product::whereIn('id', $productIds)->get();
            $updatedCount = 0;

            foreach ($products as $product) {
                $changes = [];

                // Apply brand change
                if ($request->boolean('apply_brand') && $request->filled('brand_id')) {
                    $changes['brand_id'] = $request->input('brand_id');
                }

                // Apply category change
                if ($request->boolean('apply_category') && $request->filled('category_id')) {
                    $changes['category_id'] = $request->input('category_id');
                    // Also update subcategory if provided
                    if ($request->filled('subcategory_id')) {
                        $changes['subcategory_id'] = $request->input('subcategory_id');
                    }
                }

                // Apply supplier change
                if ($request->boolean('apply_supplier') && $request->filled('supplier_id')) {
                    $changes['supplier_id'] = $request->input('supplier_id');
                }

                // Apply status change
                if ($request->boolean('apply_status')) {
                    $changes['is_active'] = $request->boolean('is_active');
                }

                // Update product if there are changes
                if (! empty($changes)) {
                    $product->update($changes);
                }

                // Apply tag changes
                if ($request->boolean('apply_tags')) {
                    $currentTagIds = $product->tags()->pluck('tags.id')->toArray();
                    $currentTags = $product->tags()->pluck('name')->toArray();

                    // Get tags to add
                    $tagsToAdd = $request->input('tags_to_add', []);
                    $newTags = [];
                    foreach ($tagsToAdd as $tagName) {
                        $tag = Tag::findOrCreateByName($tagName);
                        if (! in_array($tag->id, $currentTagIds)) {
                            $newTags[] = $tag->id;
                        }
                    }

                    // Get tags to remove
                    $tagsToRemove = $request->input('tags_to_remove', []);
                    $normalizedTagsToRemove = collect($tagsToRemove)
                        ->map(fn ($t) => strtolower(trim($t)))
                        ->toArray();

                    // Calculate final tag IDs
                    $finalTagIds = collect($currentTagIds)
                        ->reject(function ($tagId) use ($normalizedTagsToRemove) {
                            $tag = Tag::find($tagId);

                            return $tag && in_array($tag->name, $normalizedTagsToRemove);
                        })
                        ->merge($newTags)
                        ->unique()
                        ->values()
                        ->toArray();

                    $product->tags()->sync($finalTagIds);
                }

                // Apply price changes
                if ($request->boolean('apply_prices')) {
                    $priceMode = $request->input('price_mode');
                    $adjustments = collect($request->input('price_adjustments', []));

                    foreach ($adjustments as $adjustment) {
                        $currencyId = $adjustment['currency_id'];
                        $hasCostPrice = isset($adjustment['cost_price']) && $adjustment['cost_price'] !== null;
                        $hasUnitPrice = isset($adjustment['unit_price']) && $adjustment['unit_price'] !== null;

                        // Skip if no values are set for this currency
                        if (! $hasCostPrice && ! $hasUnitPrice) {
                            continue;
                        }

                        $productPrice = $product->prices()->where('currency_id', $currencyId)->first();

                        if ($priceMode === 'fixed') {
                            // Treat 0 as null (remove the price)
                            $costPrice = $hasCostPrice ? ((float) $adjustment['cost_price'] === 0.0 ? null : $adjustment['cost_price']) : null;
                            $unitPrice = $hasUnitPrice ? ((float) $adjustment['unit_price'] === 0.0 ? null : $adjustment['unit_price']) : null;

                            if ($productPrice) {
                                // Update existing price record
                                $updateData = [];
                                if ($hasCostPrice) {
                                    $updateData['cost_price'] = $costPrice;
                                }
                                if ($hasUnitPrice) {
                                    $updateData['unit_price'] = $unitPrice;
                                }
                                $productPrice->update($updateData);

                                // Delete the price record if both values become null
                                $productPrice->refresh();
                                if ($productPrice->cost_price === null && $productPrice->unit_price === null) {
                                    $productPrice->delete();
                                }
                            } elseif ($costPrice !== null || $unitPrice !== null) {
                                // Only create if at least one value is non-null
                                ProductPrice::create([
                                    'product_id' => $product->id,
                                    'currency_id' => $currencyId,
                                    'cost_price' => $costPrice,
                                    'unit_price' => $unitPrice,
                                ]);
                            }
                        } elseif ($priceMode === 'percentage' && $productPrice) {
                            // Apply percentage adjustment (only to existing prices)
                            $updateData = [];
                            if ($hasCostPrice && $productPrice->cost_price !== null) {
                                $percentChange = (float) $adjustment['cost_price'];
                                $newCostPrice = round((float) $productPrice->cost_price * (1 + $percentChange / 100), 2);
                                // Set to null if result is 0 or negative
                                $updateData['cost_price'] = $newCostPrice <= 0 ? null : $newCostPrice;
                            }
                            if ($hasUnitPrice && $productPrice->unit_price !== null) {
                                $percentChange = (float) $adjustment['unit_price'];
                                $newUnitPrice = round((float) $productPrice->unit_price * (1 + $percentChange / 100), 2);
                                // Set to null if result is 0 or negative
                                $updateData['unit_price'] = $newUnitPrice <= 0 ? null : $newUnitPrice;
                            }

                            if (! empty($updateData)) {
                                $productPrice->update($updateData);

                                // Delete the price record if both values become null
                                $productPrice->refresh();
                                if ($productPrice->cost_price === null && $productPrice->unit_price === null) {
                                    $productPrice->delete();
                                }
                            }
                        }
                    }
                }

                $updatedCount++;
            }

            return $this->respondWithSuccess(
                request(),
                'products.index',
                [],
                "{$updatedCount} product(s) updated successfully."
            );
        });
    }
}

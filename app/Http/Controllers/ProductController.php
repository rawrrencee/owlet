<?php

namespace App\Http\Controllers;

use App\Enums\WeightUnit;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Traits\RespondsWithInertiaOrJson;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductStore;
use App\Models\ProductStorePrice;
use App\Models\Store;
use App\Models\Supplier;
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

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $search = $request->query('search', '');
        $status = $request->query('status', '');
        $brandId = $request->query('brand_id', '');
        $categoryId = $request->query('category_id', '');
        $supplierId = $request->query('supplier_id', '');
        $showDeleted = $request->boolean('show_deleted', false);

        $query = Product::with([
            'brand:id,brand_name,brand_code',
            'category:id,category_name,category_code',
            'subcategory:id,subcategory_name,subcategory_code',
            'supplier:id,supplier_name',
            'prices.currency',
        ]);

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

        $products = $query
            ->orderBy('product_name')
            ->paginate(15)
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
            'categories' => Category::where('is_active', true)->orderBy('category_name')->get(['id', 'category_name', 'category_code']),
            'suppliers' => Supplier::where('active', true)->orderBy('supplier_name')->get(['id', 'supplier_name']),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'brand_id' => $brandId,
                'category_id' => $categoryId,
                'supplier_id' => $supplierId,
                'show_deleted' => $showDeleted,
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Products/Form', [
            'product' => null,
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
            $data = collect($request->validated())->except(['image', 'prices', 'stores'])->toArray();
            $product = Product::create($data);

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
                ]))
            );
        });
    }

    public function show(Request $request, Product $product): InertiaResponse|JsonResponse
    {
        $product->load([
            'brand',
            'category',
            'subcategory',
            'supplier',
            'prices.currency',
            'productStores.store',
            'productStores.storePrices.currency',
            'createdBy:id,name',
            'updatedBy:id,name',
            'previousUpdatedBy:id,name',
        ]);

        if ($this->wantsJson($request)) {
            return response()->json([
                'data' => (new ProductResource($product))->resolve(),
            ]);
        }

        return Inertia::render('Products/View', [
            'product' => (new ProductResource($product))->resolve(),
        ]);
    }

    public function edit(Product $product): InertiaResponse
    {
        $product->load([
            'brand',
            'category',
            'subcategory',
            'supplier',
            'prices.currency',
            'productStores.store',
            'productStores.storePrices.currency',
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
            $data = collect($request->validated())->except(['prices', 'stores'])->toArray();
            $product->update($data);

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
                ])))->resolve()
            );
        });
    }

    public function destroy(Request $request, Product $product): RedirectResponse|JsonResponse
    {
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
}

<?php

namespace App\Http\Controllers;

use App\Enums\BundleMode;
use App\Enums\DiscountType;
use App\Enums\OfferStatus;
use App\Enums\OfferType;
use App\Http\Requests\StoreOfferRequest;
use App\Http\Requests\UpdateOfferRequest;
use App\Http\Resources\OfferResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Store;
use App\Models\Subcategory;
use App\Services\OfferService;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class OfferController extends Controller
{
    public function __construct(
        private readonly OfferService $service,
        private readonly PermissionService $permissionService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $query = Offer::with(['category', 'brand', 'stores', 'amounts.currency', 'createdBy']);

        // Filters
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('store_id')) {
            $query->forStore((int) $request->store_id);
        }

        $offers = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        $stores = Store::select('id', 'store_name', 'store_code')
            ->orderBy('store_name')
            ->get();

        return Inertia::render('Offers/Index', [
            'offers' => OfferResource::collection($offers),
            'stores' => $stores,
            'filters' => $request->only(['search', 'type', 'status', 'store_id']),
            'typeOptions' => OfferType::options(),
            'statusOptions' => OfferStatus::options(),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Offers/Form', [
            'stores' => Store::select('id', 'store_name', 'store_code')->orderBy('store_name')->get(),
            'categories' => Category::select('id', 'category_name')->orderBy('category_name')->get(),
            'subcategories' => Subcategory::with('category:id,category_name')
                ->select('id', 'subcategory_name', 'category_id')
                ->where('is_active', true)
                ->orderBy('subcategory_name')
                ->get(),
            'brands' => Brand::select('id', 'brand_name')->orderBy('brand_name')->get(),
            'currencies' => Currency::where('active', true)->select('id', 'code', 'name', 'symbol')->orderBy('code')->get(),
            'typeOptions' => OfferType::options(),
            'discountTypeOptions' => DiscountType::options(),
            'statusOptions' => OfferStatus::options(),
            'bundleModeOptions' => BundleMode::options(),
        ]);
    }

    public function store(StoreOfferRequest $request): RedirectResponse
    {
        $offer = $this->service->create($request->validated(), $request->user());

        return redirect("/offers/{$offer->id}")
            ->with('success', 'Offer created.');
    }

    public function show(Offer $offer): InertiaResponse
    {
        $offer->load([
            'category', 'brand', 'stores',
            'amounts.currency', 'products.product',
            'bundles.product', 'bundles.category', 'bundles.subcategory.category',
            'minimumSpends.currency', 'createdBy',
        ]);

        return Inertia::render('Offers/View', [
            'offer' => OfferResource::make($offer)->resolve(),
        ]);
    }

    /**
     * Get offer details as JSON (for dialog display).
     */
    public function showJson(Offer $offer): JsonResponse
    {
        $offer->load([
            'category', 'brand', 'stores',
            'amounts.currency', 'products.product',
            'bundles.product', 'bundles.category', 'bundles.subcategory.category',
            'minimumSpends.currency',
        ]);

        return response()->json(OfferResource::make($offer)->resolve());
    }

    public function edit(Offer $offer): InertiaResponse
    {
        $offer->load([
            'category', 'brand', 'stores',
            'amounts.currency', 'products.product',
            'bundles.product', 'bundles.category', 'bundles.subcategory.category',
            'minimumSpends.currency',
        ]);

        return Inertia::render('Offers/Form', [
            'offer' => OfferResource::make($offer)->resolve(),
            'stores' => Store::select('id', 'store_name', 'store_code')->orderBy('store_name')->get(),
            'categories' => Category::select('id', 'category_name')->orderBy('category_name')->get(),
            'subcategories' => Subcategory::with('category:id,category_name')
                ->select('id', 'subcategory_name', 'category_id')
                ->where('is_active', true)
                ->orderBy('subcategory_name')
                ->get(),
            'brands' => Brand::select('id', 'brand_name')->orderBy('brand_name')->get(),
            'currencies' => Currency::where('active', true)->select('id', 'code', 'name', 'symbol')->orderBy('code')->get(),
            'typeOptions' => OfferType::options(),
            'discountTypeOptions' => DiscountType::options(),
            'statusOptions' => OfferStatus::options(),
            'bundleModeOptions' => BundleMode::options(),
        ]);
    }

    public function update(UpdateOfferRequest $request, Offer $offer): RedirectResponse
    {
        $this->service->update($offer, $request->validated(), $request->user());

        return redirect("/offers/{$offer->id}")
            ->with('success', 'Offer updated.');
    }

    public function destroy(Offer $offer): RedirectResponse
    {
        $this->service->delete($offer);

        return redirect('/offers')
            ->with('success', 'Offer deleted.');
    }

    public function activate(Offer $offer): RedirectResponse
    {
        $this->service->activate($offer);

        return redirect("/offers/{$offer->id}")
            ->with('success', 'Offer activated.');
    }

    public function disable(Offer $offer): RedirectResponse
    {
        $this->service->disable($offer);

        return redirect("/offers/{$offer->id}")
            ->with('success', 'Offer disabled.');
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
}

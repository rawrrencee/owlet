<?php

namespace App\Http\Controllers;

use App\Constants\StorePermissions;
use App\Enums\StocktakeStatus;
use App\Http\Requests\AddStocktakeItemRequest;
use App\Http\Requests\StoreStocktakeRequest;
use App\Http\Requests\UpdateStocktakeItemRequest;
use App\Http\Resources\StocktakeItemResource;
use App\Http\Resources\StocktakeResource;
use App\Http\Resources\StocktakeTemplateResource;
use App\Models\Product;
use App\Models\Stocktake;
use App\Models\StocktakeItem;
use App\Models\StocktakeTemplate;
use App\Services\PermissionService;
use App\Services\StocktakeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class StocktakeController extends Controller
{
    public function __construct(
        private readonly StocktakeService $stocktakeService,
        private readonly PermissionService $permissionService
    ) {}

    /**
     * Store selection + active session display.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            abort(403, 'You must have an employee record to perform stocktakes.');
        }

        // Get stores where employee has stocktake permission
        $stores = $employee->employeeStores()
            ->where('active', true)
            ->get()
            ->filter(fn ($es) => $es->hasPermission(StorePermissions::STOCKTAKE))
            ->map(fn ($es) => $es->store)
            ->filter()
            ->values()
            ->map(fn ($store) => [
                'id' => $store->id,
                'store_name' => $store->store_name,
                'store_code' => $store->store_code,
            ]);

        // Get active in-progress stocktake
        $activeStocktake = Stocktake::where('employee_id', $employee->id)
            ->inProgress()
            ->with('store')
            ->withCount('items')
            ->first();

        // Get templates for the employee
        $templates = StocktakeTemplate::where('employee_id', $employee->id)
            ->with('store')
            ->withCount('products')
            ->get();

        // Get recently submitted stocktakes
        $recentStocktakes = Stocktake::where('employee_id', $employee->id)
            ->submitted()
            ->with('store')
            ->withCount('items')
            ->orderByDesc('submitted_at')
            ->limit(10)
            ->get();

        return Inertia::render('Stocktakes/Index', [
            'stores' => $stores,
            'activeStocktake' => $activeStocktake ? StocktakeResource::make($activeStocktake)->resolve() : null,
            'templates' => StocktakeTemplateResource::collection($templates)->resolve(),
            'recentStocktakes' => StocktakeResource::collection($recentStocktakes)->resolve(),
        ]);
    }

    /**
     * JSON endpoint for current stocktake state (used by floating widget).
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'can_stocktake' => false,
                'active_stocktake' => null,
                'stores' => [],
            ]);
        }

        // Check if user has stocktakes.submit page permission
        $permissionService = app(PermissionService::class);
        if (! $user->isAdmin() && ! $permissionService->canAccessPage($user, 'stocktakes.submit')) {
            return response()->json([
                'can_stocktake' => false,
                'active_stocktake' => null,
                'stores' => [],
            ]);
        }

        $activeStocktake = Stocktake::where('employee_id', $employee->id)
            ->inProgress()
            ->with('store')
            ->withCount('items')
            ->first();

        // Get stores where employee has stocktake permission
        if ($user->isAdmin()) {
            $stores = \App\Models\Store::select('id', 'store_name', 'store_code')
                ->orderBy('store_name')
                ->get()
                ->toArray();
        } else {
            $stores = $employee->employeeStores()
                ->where('active', true)
                ->get()
                ->filter(fn ($es) => $es->hasPermission(StorePermissions::STOCKTAKE))
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

        return response()->json([
            'can_stocktake' => true,
            'active_stocktake' => $activeStocktake ? StocktakeResource::make($activeStocktake)->resolve() : null,
            'stores' => $stores,
        ]);
    }

    /**
     * Start/resume a stocktake at a store.
     */
    public function store(StoreStocktakeRequest $request): RedirectResponse
    {
        $user = $request->user();
        $employee = $user->employee;
        $storeId = $request->validated('store_id');

        if (! $employee) {
            abort(403);
        }

        // Check store operation permission
        if (! $employee->hasStorePermission($storeId, StorePermissions::STOCKTAKE) && ! $user->isAdmin()) {
            abort(403, 'You do not have permission to perform stocktakes at this store.');
        }

        $store = \App\Models\Store::findOrFail($storeId);
        $stocktake = $this->stocktakeService->getOrCreateStocktake($employee, $store);

        return redirect()->route('stocktakes.show', $stocktake);
    }

    /**
     * Item counting interface.
     */
    public function show(Request $request, Stocktake $stocktake): InertiaResponse
    {
        $this->authorizeStocktake($request, $stocktake);

        $stocktake->load(['items.product', 'store', 'employee']);

        $canViewDifference = $this->stocktakeService->canViewDifference(
            $request->user(),
            $stocktake->store_id
        );

        // Get templates for this store
        $templates = StocktakeTemplate::where('employee_id', $stocktake->employee_id)
            ->where('store_id', $stocktake->store_id)
            ->withCount('products')
            ->get();

        return Inertia::render('Stocktakes/Show', [
            'stocktake' => StocktakeResource::make($stocktake)->resolve(),
            'canViewDifference' => $canViewDifference,
            'templates' => StocktakeTemplateResource::collection($templates)->resolve(),
        ]);
    }

    /**
     * Add a product with count to the stocktake.
     */
    public function addItem(AddStocktakeItemRequest $request, Stocktake $stocktake): RedirectResponse
    {
        $this->authorizeStocktake($request, $stocktake);
        $this->ensureInProgress($stocktake);

        $validated = $request->validated();

        // Check if product already exists in stocktake
        $existing = $stocktake->items()->where('product_id', $validated['product_id'])->first();
        if ($existing) {
            return back()->withErrors(['product_id' => 'This product has already been added to the stocktake.']);
        }

        $this->stocktakeService->addItem($stocktake, $validated['product_id'], $validated['counted_quantity']);

        return back();
    }

    /**
     * Update the counted quantity for an item.
     */
    public function updateItem(UpdateStocktakeItemRequest $request, Stocktake $stocktake, StocktakeItem $item): RedirectResponse
    {
        $this->authorizeStocktake($request, $stocktake);
        $this->ensureInProgress($stocktake);
        $this->ensureItemBelongsToStocktake($item, $stocktake);

        $this->stocktakeService->updateItem($item, $request->validated('counted_quantity'));

        return back();
    }

    /**
     * Remove an item from the stocktake.
     */
    public function removeItem(Request $request, Stocktake $stocktake, StocktakeItem $item): RedirectResponse
    {
        $this->authorizeStocktake($request, $stocktake);
        $this->ensureInProgress($stocktake);
        $this->ensureItemBelongsToStocktake($item, $stocktake);

        $this->stocktakeService->removeItem($item);

        return back();
    }

    /**
     * Submit the stocktake.
     */
    public function submit(Request $request, Stocktake $stocktake): RedirectResponse
    {
        $this->authorizeStocktake($request, $stocktake);
        $this->ensureInProgress($stocktake);

        if ($stocktake->items()->count() === 0) {
            return back()->withErrors(['items' => 'Cannot submit a stocktake with no items.']);
        }

        $this->stocktakeService->submit($stocktake);

        return redirect()->route('stocktakes.index')
            ->with('success', 'Stocktake submitted successfully.');
    }

    /**
     * Delete an in-progress stocktake.
     */
    public function destroy(Request $request, Stocktake $stocktake): RedirectResponse
    {
        $this->authorizeStocktake($request, $stocktake);
        $this->ensureInProgress($stocktake);

        $this->stocktakeService->delete($stocktake);

        return redirect()->route('stocktakes.index')
            ->with('success', 'Stocktake deleted.');
    }

    /**
     * Search store products for adding to stocktake.
     */
    public function searchProducts(Request $request, Stocktake $stocktake): JsonResponse
    {
        $this->authorizeStocktake($request, $stocktake);

        $search = $request->query('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        // Get product IDs already in stocktake
        $existingIds = $stocktake->items()->pluck('product_id')->toArray();

        $products = Product::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereHas('productStores', function ($q) use ($stocktake) {
                $q->where('store_id', $stocktake->store_id);
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

    /**
     * Apply a template's products to the stocktake.
     */
    public function applyTemplate(Request $request, Stocktake $stocktake): RedirectResponse
    {
        $this->authorizeStocktake($request, $stocktake);
        $this->ensureInProgress($stocktake);

        $request->validate([
            'template_id' => ['required', 'exists:stocktake_templates,id'],
        ]);

        $template = StocktakeTemplate::findOrFail($request->template_id);

        $added = $this->stocktakeService->addItemsFromTemplate($stocktake, $template);

        return back()->with('success', "{$added} item(s) added from template.");
    }

    /**
     * Authorize that the user owns the stocktake and has permission.
     */
    protected function authorizeStocktake(Request $request, Stocktake $stocktake): void
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $user->isAdmin()) {
            if (! $employee || $stocktake->employee_id !== $employee->id) {
                abort(403, 'You do not own this stocktake.');
            }

            if (! $employee->hasStorePermission($stocktake->store_id, StorePermissions::STOCKTAKE)) {
                abort(403, 'You do not have stocktake permission at this store.');
            }
        }
    }

    protected function ensureInProgress(Stocktake $stocktake): void
    {
        if ($stocktake->status !== StocktakeStatus::IN_PROGRESS) {
            abort(422, 'This stocktake is no longer in progress.');
        }
    }

    protected function ensureItemBelongsToStocktake(StocktakeItem $item, Stocktake $stocktake): void
    {
        if ($item->stocktake_id !== $stocktake->id) {
            abort(404);
        }
    }
}

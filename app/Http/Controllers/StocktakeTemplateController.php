<?php

namespace App\Http\Controllers;

use App\Constants\StorePermissions;
use App\Http\Requests\StoreStocktakeTemplateRequest;
use App\Http\Requests\UpdateStocktakeTemplateRequest;
use App\Http\Resources\StocktakeTemplateResource;
use App\Models\Product;
use App\Models\StocktakeTemplate;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class StocktakeTemplateController extends Controller
{
    // ─── Staff routes ───

    /**
     * List own templates.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            abort(403);
        }

        $templates = StocktakeTemplate::where('employee_id', $employee->id)
            ->with(['store'])
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return Inertia::render('Stocktakes/Templates/Index', [
            'templates' => StocktakeTemplateResource::collection($templates)->resolve(),
        ]);
    }

    /**
     * Create template form.
     */
    public function create(Request $request): InertiaResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            abort(403);
        }

        $stores = $this->getStocktakeStores($employee, $user);

        return Inertia::render('Stocktakes/Templates/Form', [
            'stores' => $stores,
            'template' => null,
        ]);
    }

    /**
     * Store a new template.
     */
    public function store(StoreStocktakeTemplateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            abort(403);
        }

        $validated = $request->validated();

        $template = StocktakeTemplate::create([
            'employee_id' => $employee->id,
            'store_id' => $validated['store_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $template->products()->sync($validated['product_ids']);

        return redirect()->route('stocktake-templates.index')
            ->with('success', 'Template created successfully.');
    }

    /**
     * Edit template form.
     */
    public function edit(Request $request, StocktakeTemplate $stocktakeTemplate): InertiaResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee || $stocktakeTemplate->employee_id !== $employee->id) {
            abort(403);
        }

        $stocktakeTemplate->load(['products', 'store']);
        $stores = $this->getStocktakeStores($employee, $user);

        return Inertia::render('Stocktakes/Templates/Form', [
            'stores' => $stores,
            'template' => StocktakeTemplateResource::make($stocktakeTemplate)->resolve(),
        ]);
    }

    /**
     * Update a template.
     */
    public function update(UpdateStocktakeTemplateRequest $request, StocktakeTemplate $stocktakeTemplate): RedirectResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee || $stocktakeTemplate->employee_id !== $employee->id) {
            abort(403);
        }

        $validated = $request->validated();

        $stocktakeTemplate->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $stocktakeTemplate->products()->sync($validated['product_ids']);

        return redirect()->route('stocktake-templates.index')
            ->with('success', 'Template updated successfully.');
    }

    /**
     * Delete a template.
     */
    public function destroy(Request $request, StocktakeTemplate $stocktakeTemplate): RedirectResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee || $stocktakeTemplate->employee_id !== $employee->id) {
            abort(403);
        }

        $stocktakeTemplate->delete();

        return redirect()->route('stocktake-templates.index')
            ->with('success', 'Template deleted.');
    }

    // ─── Admin routes ───

    /**
     * List ALL templates (admin).
     */
    public function adminIndex(Request $request): InertiaResponse
    {
        $search = $request->query('search');
        $storeId = $request->query('store_id');

        $query = StocktakeTemplate::with(['employee', 'store'])
            ->withCount('products')
            ->orderBy('name');

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $templates = $query->paginate(20);
        $stores = Store::select('id', 'store_name', 'store_code')->orderBy('store_name')->get();

        return Inertia::render('Management/StocktakeTemplates/Index', [
            'templates' => StocktakeTemplateResource::collection($templates)->resolve(),
            'pagination' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
            ],
            'stores' => $stores,
            'filters' => [
                'search' => $search,
                'store_id' => $storeId,
            ],
        ]);
    }

    /**
     * Edit any template (admin).
     */
    public function adminEdit(Request $request, StocktakeTemplate $template): InertiaResponse
    {
        $template->load(['products', 'store', 'employee']);
        $stores = Store::select('id', 'store_name', 'store_code')->orderBy('store_name')->get();

        return Inertia::render('Management/StocktakeTemplates/Edit', [
            'template' => StocktakeTemplateResource::make($template)->resolve(),
            'stores' => $stores,
        ]);
    }

    /**
     * Update any template (admin).
     */
    public function adminUpdate(UpdateStocktakeTemplateRequest $request, StocktakeTemplate $template): RedirectResponse
    {
        $validated = $request->validated();

        $template->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $template->products()->sync($validated['product_ids']);

        return redirect()->route('management.stocktake-templates.index')
            ->with('success', 'Template updated successfully.');
    }

    /**
     * Delete any template (admin).
     */
    public function adminDestroy(Request $request, StocktakeTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('management.stocktake-templates.index')
            ->with('success', 'Template deleted.');
    }

    /**
     * Search products assigned to a specific store (JSON).
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $search = $request->query('q', '');
        $storeId = $request->query('store_id');

        if (strlen($search) < 2 || ! $storeId) {
            return response()->json([]);
        }

        $products = Product::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereHas('productStores', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->search($search)
            ->limit(20)
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'product_name' => $product->product_name,
                'product_number' => $product->product_number,
                'variant_name' => $product->variant_name,
                'image_url' => $product->image_path ? route('products.image', $product->id) : null,
            ]);

        return response()->json($products);
    }

    /**
     * Get stores where employee has stocktake permission.
     */
    protected function getStocktakeStores($employee, $user): array
    {
        if ($user->isAdmin()) {
            return Store::select('id', 'store_name', 'store_code')
                ->orderBy('store_name')
                ->get()
                ->toArray();
        }

        return $employee->employeeStores()
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
}

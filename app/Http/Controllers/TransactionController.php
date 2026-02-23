<?php

namespace App\Http\Controllers;

use App\Constants\StorePermissions;
use App\Enums\TransactionStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PaymentMode;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Services\OfferService;
use App\Services\PermissionService;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly OfferService $offerService,
        private readonly PermissionService $permissionService
    ) {}

    /**
     * POS main page (Inertia).
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            abort(403, 'You must have an employee record to use the POS.');
        }

        // Get stores where employee has process_sales permission
        $stores = $employee->employeeStores()
            ->where('active', true)
            ->get()
            ->filter(fn ($es) => $es->hasPermission(StorePermissions::PROCESS_SALES))
            ->filter(fn ($es) => $es->store !== null)
            ->values()
            ->map(fn ($es) => [
                'id' => $es->store->id,
                'store_name' => $es->store->store_name,
                'store_code' => $es->store->store_code,
                'tax_percentage' => $es->store->tax_percentage,
                'include_tax' => $es->store->include_tax,
                'can_void' => $es->hasPermission(StorePermissions::VOID_SALES),
                'can_apply_discounts' => $es->hasPermission(StorePermissions::APPLY_DISCOUNTS),
                'currencies' => $es->store->currencies->map(fn ($c) => [
                    'id' => $c->id,
                    'code' => $c->code,
                    'symbol' => $c->symbol,
                    'name' => $c->name,
                    'exchange_rate' => (string) $c->exchange_rate,
                ]),
            ]);

        $paymentModes = PaymentMode::active()->ordered()->get()->map(fn ($pm) => [
            'id' => $pm->id,
            'name' => $pm->name,
            'code' => $pm->code,
        ]);

        return Inertia::render('Pos/Index', [
            'stores' => $stores,
            'paymentModes' => $paymentModes,
            'employeeId' => $employee->id,
        ]);
    }

    /**
     * Get products for a store (paginated grid data).
     */
    public function products(Request $request): JsonResponse
    {
        $storeId = $request->integer('store_id');
        $currencyId = $request->integer('currency_id');
        $categoryId = $request->integer('category_id') ?: null;
        $brandId = $request->integer('brand_id') ?: null;
        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 40);

        $query = Product::active()
            ->forPosDisplay()
            ->whereHas('productStores', fn ($q) => $q->where('store_id', $storeId)->where('is_active', true))
            ->with([
                'variants' => fn ($q) => $q->active(),
                'variants.productStores' => fn ($q) => $q->where('store_id', $storeId),
                'variants.productStores.storePrices' => fn ($q) => $q->where('currency_id', $currencyId),
                'variants.prices' => fn ($q) => $q->where('currency_id', $currencyId),
                'productStores' => fn ($q) => $q->where('store_id', $storeId),
                'productStores.storePrices' => fn ($q) => $q->where('currency_id', $currencyId),
                'prices',
                'category',
                'subcategory',
                'brand',
            ]);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        if ($request->integer('subcategory_id')) {
            $query->where('subcategory_id', $request->integer('subcategory_id'));
        }
        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        $products = $query->orderBy('product_name')->paginate($perPage, ['*'], 'page', $page);

        return response()->json($products);
    }

    /**
     * Search products by name/number/barcode.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $storeId = $request->integer('store_id');
        $currencyId = $request->integer('currency_id');

        if (strlen($search) < 1) {
            return response()->json([]);
        }

        $products = Product::active()
            ->search($search)
            ->whereHas('productStores', fn ($q) => $q->where('store_id', $storeId)->where('is_active', true))
            ->with([
                'variants' => fn ($q) => $q->active(),
                'productStores' => fn ($q) => $q->where('store_id', $storeId),
                'productStores.storePrices' => fn ($q) => $q->where('currency_id', $currencyId),
                'prices' => fn ($q) => $q->where('currency_id', $currencyId),
            ])
            ->limit(50)
            ->get();

        return response()->json($products);
    }

    /**
     * Search customers.
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();

        if (strlen($search) < 1) {
            return response()->json([]);
        }

        $customers = Customer::where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        })
            ->limit(50)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'first_name' => $c->first_name,
                'last_name' => $c->last_name,
                'full_name' => $c->first_name . ' ' . $c->last_name,
                'email' => $c->email,
                'phone' => $c->phone,
                'discount_percentage' => $c->discount_percentage,
            ]);

        return response()->json($customers);
    }

    /**
     * Get suspended transactions for a store.
     */
    public function suspendedList(Request $request): JsonResponse
    {
        $storeId = $request->integer('store_id');

        $transactions = Transaction::forStore($storeId)
            ->suspended()
            ->with(['employee', 'customer', 'currency'])
            ->withCount('items')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json($transactions);
    }

    /**
     * Create a new draft transaction.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'currency_id' => 'required|exists:currencies,id',
        ]);

        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            abort(403, 'You must have an employee record to create transactions.');
        }

        // Verify PROCESS_SALES permission for the requested store
        $es = $employee->employeeStores()
            ->where('store_id', $request->integer('store_id'))
            ->where('active', true)
            ->first();

        if (! $es || ! $es->hasPermission(StorePermissions::PROCESS_SALES)) {
            abort(403, 'You do not have permission to process sales for this store.');
        }

        $transaction = $this->transactionService->findOrCreateDraft(
            $request->integer('store_id'),
            $employee->id,
            $request->integer('currency_id'),
            $user->id
        );

        return response()->json($transaction, 201);
    }

    /**
     * Get the current employee's draft transaction for a store.
     */
    public function currentDraft(Request $request): JsonResponse|\Illuminate\Http\Response
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
        ]);

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->noContent();
        }

        $draft = Transaction::forStore($request->integer('store_id'))
            ->forEmployee($employee->id)
            ->draft()
            ->orderByDesc('updated_at')
            ->first();

        if (! $draft) {
            return response()->noContent();
        }

        $draft->load([
            'store', 'employee', 'customer', 'currency',
            'items.product', 'payments.paymentMode',
        ]);

        return response()->json($draft);
    }

    /**
     * Get full transaction state.
     */
    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load([
            'store', 'employee', 'customer', 'currency',
            'items.product', 'payments.paymentMode',
        ]);

        return response()->json($transaction);
    }

    /**
     * Add item to transaction.
     */
    public function addItem(Request $request, Transaction $transaction): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $transaction = $this->transactionService->addItem(
            $transaction,
            $request->integer('product_id'),
            $request->integer('quantity'),
            $request->user()->id
        );

        return response()->json($transaction);
    }

    /**
     * Update item on transaction.
     */
    public function updateItem(Request $request, Transaction $transaction, int $item): JsonResponse
    {
        $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'unit_price' => 'sometimes|numeric|min:0',
        ]);

        $transaction = $this->transactionService->updateItem(
            $transaction,
            $item,
            $request->only(['quantity', 'unit_price']),
            $request->user()->id
        );

        return response()->json($transaction);
    }

    /**
     * Remove item from transaction.
     */
    public function removeItem(Request $request, Transaction $transaction, int $item): JsonResponse
    {
        $transaction = $this->transactionService->removeItem(
            $transaction,
            $item,
            $request->user()->id
        );

        return response()->json($transaction);
    }

    /**
     * Set customer on transaction.
     */
    public function setCustomer(Request $request, Transaction $transaction): JsonResponse
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $transaction = $this->transactionService->setCustomer(
            $transaction,
            $request->input('customer_id'),
            $request->user()->id
        );

        return response()->json($transaction);
    }

    /**
     * Clear customer discount without removing the customer.
     */
    public function clearCustomerDiscount(Transaction $transaction): JsonResponse
    {
        $transaction = $this->transactionService->clearCustomerDiscount(
            $transaction,
            request()->user()->id
        );

        return response()->json($transaction);
    }

    /**
     * Restore customer discount on transaction.
     */
    public function restoreCustomerDiscount(Request $request, Transaction $transaction): JsonResponse
    {
        $transaction = $this->transactionService->restoreCustomerDiscount(
            $transaction,
            $request->user()->id
        );

        return response()->json($transaction);
    }

    /**
     * Apply a manual discount to the transaction.
     */
    public function applyManualDiscount(Request $request, Transaction $transaction): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:percentage,amount',
            'value' => 'required|numeric|gt:0',
        ]);

        // Check APPLY_DISCOUNTS permission
        $employee = $request->user()->employee;
        $es = $employee?->employeeStores()->where('store_id', $transaction->store_id)->where('active', true)->first();
        if (! $es || ! $es->hasPermission(StorePermissions::APPLY_DISCOUNTS)) {
            abort(403, 'You do not have permission to apply discounts for this store.');
        }

        $transaction = $this->transactionService->applyManualDiscount(
            $transaction,
            $request->input('type'),
            (string) $request->input('value'),
            $request->user()->id
        );

        return response()->json($transaction);
    }

    /**
     * Clear manual discount from transaction.
     */
    public function clearManualDiscount(Request $request, Transaction $transaction): JsonResponse
    {
        $transaction = $this->transactionService->clearManualDiscount(
            $transaction,
            $request->user()->id
        );

        return response()->json($transaction);
    }

    /**
     * Add payment to transaction.
     */
    public function addPayment(Request $request, Transaction $transaction): JsonResponse
    {
        $request->validate([
            'payment_mode_id' => 'required|exists:payment_modes,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_data' => 'nullable|array',
        ]);

        $transaction = $this->transactionService->addPayment(
            $transaction,
            $request->integer('payment_mode_id'),
            (string) $request->input('amount'),
            $request->input('payment_data'),
            $request->user()->id
        );

        return response()->json($transaction);
    }

    /**
     * Remove payment from transaction.
     */
    public function removePayment(Request $request, Transaction $transaction, int $payment): JsonResponse
    {
        $transaction = $this->transactionService->removePayment(
            $transaction,
            $payment,
            $request->user()->id
        );

        return response()->json($transaction);
    }

    /**
     * Complete (finalize) transaction.
     */
    public function complete(Request $request, Transaction $transaction): JsonResponse
    {
        $transaction = $this->transactionService->complete(
            $transaction,
            $request->user()->id
        );

        return response()->json($transaction);
    }

    /**
     * Suspend (park) transaction.
     */
    public function suspend(Request $request, Transaction $transaction): JsonResponse
    {
        $transaction = $this->transactionService->suspend(
            $transaction,
            $request->user()->id
        );

        return response()->json($transaction);
    }

    /**
     * Resume suspended transaction.
     */
    public function resume(Request $request, Transaction $transaction): JsonResponse
    {
        $transaction = $this->transactionService->resume(
            $transaction,
            $request->user()->id
        );

        return response()->json($transaction);
    }

    /**
     * Void transaction.
     */
    public function voidTransaction(Request $request, Transaction $transaction): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        // Check void permission for the transaction's store
        $employee = $request->user()->employee;
        $es = $employee?->employeeStores()->where('store_id', $transaction->store_id)->where('active', true)->first();
        if (! $es || ! $es->hasPermission(StorePermissions::VOID_SALES)) {
            abort(403, 'You do not have permission to void sales for this store.');
        }

        $transaction = $this->transactionService->void(
            $transaction,
            $request->user()->id,
            $request->input('reason')
        );

        return response()->json($transaction);
    }

    /**
     * Process refund.
     */
    public function refund(Request $request, Transaction $transaction): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer|exists:transaction_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.reason' => 'nullable|string|max:500',
        ]);

        // Check void permission for the transaction's store (refund uses same permission)
        $employee = $request->user()->employee;
        $es = $employee?->employeeStores()->where('store_id', $transaction->store_id)->where('active', true)->first();
        if (! $es || ! $es->hasPermission(StorePermissions::VOID_SALES)) {
            abort(403, 'You do not have permission to process refunds for this store.');
        }

        $transaction = $this->transactionService->processRefund(
            $transaction,
            $request->input('items'),
            $request->user()->id
        );

        return response()->json($transaction);
    }

    /**
     * Get active offers for a store (for the offers dialog).
     */
    public function offers(Request $request): JsonResponse
    {
        $storeId = $request->integer('store_id');
        $offers = $this->offerService->getActiveOffersForStore($storeId);

        return response()->json($offers);
    }

    /**
     * Get all categories (with subcategories) and brands that have active products in a store.
     */
    public function filters(Request $request): JsonResponse
    {
        $storeId = $request->integer('store_id');

        $productBase = Product::active()
            ->forPosDisplay()
            ->whereHas('productStores', fn ($q) => $q->where('store_id', $storeId)->where('is_active', true));

        $categoryIds = (clone $productBase)->distinct()->pluck('category_id')->filter();
        $brandIds = (clone $productBase)->distinct()->pluck('brand_id')->filter();

        $categories = Category::whereIn('id', $categoryIds)
            ->with(['activeSubcategories' => fn ($q) => $q->orderBy('subcategory_name')])
            ->orderBy('category_name')
            ->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'category_name' => $cat->category_name,
                'subcategories' => $cat->activeSubcategories->map(fn ($sub) => [
                    'id' => $sub->id,
                    'subcategory_name' => $sub->subcategory_name,
                ]),
            ]);

        $brands = Brand::whereIn('id', $brandIds)
            ->orderBy('brand_name')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'brand_name' => $b->brand_name,
            ]);

        return response()->json([
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    /**
     * Get favourite product IDs for the current employee.
     */
    public function favourites(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        if (! $employee) {
            return response()->json([]);
        }

        $ids = \App\Models\FavouriteProduct::where('employee_id', $employee->id)
            ->pluck('product_id');

        return response()->json($ids);
    }

    /**
     * Toggle a product as favourite for the current employee.
     */
    public function toggleFavourite(Request $request, Product $product): JsonResponse
    {
        $employee = $request->user()->employee;
        if (! $employee) {
            abort(403, 'You must have an employee record.');
        }

        $existing = \App\Models\FavouriteProduct::where('employee_id', $employee->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['favourited' => false]);
        }

        \App\Models\FavouriteProduct::create([
            'employee_id' => $employee->id,
            'product_id' => $product->id,
        ]);

        return response()->json(['favourited' => true]);
    }
}

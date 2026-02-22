<?php

namespace App\Http\Controllers;

use App\Constants\StorePermissions;
use App\Enums\TransactionStatus;
use App\Models\PaymentMode;
use App\Models\Store;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TransactionHistoryController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * Transaction list page (Inertia).
     */
    public function index(Request $request): InertiaResponse
    {
        $query = Transaction::with(['store', 'employee', 'customer', 'currency'])
            ->withCount('items');

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }
        if ($request->filled('store_id')) {
            $query->forStore($request->integer('store_id'));
        }

        // Default to 'completed' when no status param is present.
        // When user explicitly sends status (even empty string for "All"), respect that.
        $statusFilter = $request->has('status') ? $request->input('status') : 'completed';
        if ($statusFilter) {
            $query->ofStatus($statusFilter);
        }

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $query->dateRange($request->input('start_date'), $request->input('end_date'));
        }

        $sortField = $request->input('sort_field', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $transactions = $query->paginate($request->integer('per_page', 20));

        $stores = Store::orderBy('store_name')->get(['id', 'store_name', 'store_code']);
        $statuses = TransactionStatus::options();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'stores' => $stores,
            'statuses' => $statuses,
            'filters' => array_merge(
                $request->only(['search', 'store_id', 'start_date', 'end_date', 'sort_field', 'sort_order']),
                ['status' => $statusFilter],
            ),
        ]);
    }

    /**
     * Transaction detail page (Inertia).
     */
    public function show(Request $request, Transaction $transaction): InertiaResponse
    {
        $transaction->load([
            'store', 'employee', 'customer', 'currency',
            'items.product', 'payments.paymentMode', 'payments.createdByUser',
            'versions.changedByUser',
            'createdBy', 'updatedBy',
        ]);

        // Transform to avoid created_by column/relation name conflict
        $data = $transaction->toArray();
        $data['created_by_user'] = $transaction->createdBy?->only('id', 'name');
        $data['updated_by_user'] = $transaction->updatedBy?->only('id', 'name');

        $canVoid = $this->canVoidTransaction($request, $transaction);
        $paymentModes = PaymentMode::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        // Compute eligible offers for completed transactions with void permission
        $eligibleOffers = null;
        if ($canVoid && $transaction->status === TransactionStatus::COMPLETED) {
            $eligibleOffers = $this->transactionService->getEligibleOffers($transaction);
        }

        // Check if transaction has any applied offers (product-level or cart-level)
        $hasAppliedOffers = $transaction->items->contains(fn ($item) => $item->offer_id !== null && ! $item->is_refunded)
            || $transaction->bundle_offer_id !== null
            || $transaction->minimum_spend_offer_id !== null;

        return Inertia::render('Transactions/View', [
            'transaction' => $data,
            'canVoid' => $canVoid,
            'paymentModes' => $paymentModes,
            'eligibleOffers' => $eligibleOffers,
            'hasAppliedOffers' => $hasAppliedOffers,
        ]);
    }

    /**
     * Process a refund on a completed transaction.
     */
    public function refund(Request $request, Transaction $transaction): RedirectResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer|exists:transaction_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.reason' => 'nullable|string|max:500',
        ]);

        $this->authorizeVoidPermission($request, $transaction);

        $this->transactionService->processRefund(
            $transaction,
            $request->input('items'),
            $request->user()->id
        );

        return redirect()->back();
    }

    /**
     * Void a transaction.
     */
    public function voidTransaction(Request $request, Transaction $transaction): RedirectResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->authorizeVoidPermission($request, $transaction);

        $this->transactionService->void(
            $transaction,
            $request->user()->id,
            $request->input('reason')
        );

        return redirect()->back();
    }

    /**
     * Update an item on a transaction (quantity/price adjustment).
     */
    public function updateItem(Request $request, Transaction $transaction, int $item): RedirectResponse
    {
        $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'unit_price' => 'sometimes|numeric|min:0',
        ]);

        $this->authorizeVoidPermission($request, $transaction);

        $wasCompleted = $transaction->status === TransactionStatus::COMPLETED;

        $result = $this->transactionService->updateItem(
            $transaction,
            $item,
            $request->only(['quantity', 'unit_price']),
            $request->user()->id
        );

        if ($wasCompleted) {
            $modifiedItem = $result->items->find($item);
            $changes = [];
            if ($request->has('quantity')) {
                $changes[] = "Quantity updated to {$request->input('quantity')}";
            }
            if ($request->has('unit_price')) {
                $changes[] = "Price updated to {$request->input('unit_price')}";
            }
            $changeDetails = [
                'type' => 'item_modified',
                'product_name' => $modifiedItem?->product_name ?? 'Unknown',
                'changes' => $changes,
            ];
            $this->transactionService->sendAmendedNotification($result, 'Item modified: ' . implode(', ', $changes), $changeDetails);
        }

        return redirect()->back();
    }

    /**
     * Remove an item from a transaction.
     */
    public function removeItem(Request $request, Transaction $transaction, int $item): RedirectResponse
    {
        $this->authorizeVoidPermission($request, $transaction);

        $wasCompleted = $transaction->status === TransactionStatus::COMPLETED;
        $itemModel = $wasCompleted ? $transaction->items()->find($item) : null;

        $result = $this->transactionService->removeItem(
            $transaction,
            $item,
            $request->user()->id
        );

        if ($wasCompleted && $itemModel) {
            $changeDetails = [
                'type' => 'item_removed',
                'items' => [[
                    'product_name' => $itemModel->product_name,
                    'variant_name' => $itemModel->variant_name,
                    'quantity' => $itemModel->quantity,
                ]],
            ];
            $this->transactionService->sendAmendedNotification($result, "Item removed: {$itemModel->product_name}", $changeDetails);
        }

        return redirect()->back();
    }

    /**
     * Add an item to a transaction.
     */
    public function addItem(Request $request, Transaction $transaction): RedirectResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $this->authorizeVoidPermission($request, $transaction);

        $wasCompleted = $transaction->status === TransactionStatus::COMPLETED;

        $result = $this->transactionService->addItem(
            $transaction,
            $request->integer('product_id'),
            $request->integer('quantity'),
            $request->user()->id,
            applyOffers: ! $wasCompleted
        );

        if ($wasCompleted) {
            $addedItem = $result->items->firstWhere('product_id', $request->integer('product_id'));
            $changeDetails = [
                'type' => 'item_added',
                'items' => [[
                    'product_name' => $addedItem?->product_name ?? 'Unknown',
                    'variant_name' => $addedItem?->variant_name,
                    'quantity' => $request->integer('quantity'),
                ]],
            ];
            $this->transactionService->sendAmendedNotification($result, "Item added to completed transaction", $changeDetails);
        }

        return redirect()->back();
    }

    /**
     * Apply/re-apply offers to a completed transaction.
     */
    public function applyOffers(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeVoidPermission($request, $transaction);

        $wasCompleted = $transaction->status === TransactionStatus::COMPLETED;

        $result = $this->transactionService->applyOffersToTransaction(
            $transaction,
            $request->user()->id
        );

        if ($wasCompleted) {
            $this->transactionService->sendAmendedNotification($result, 'Offers applied to transaction');
        }

        return redirect()->back();
    }

    /**
     * Clear all applied offers from a transaction.
     */
    public function clearOffers(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeVoidPermission($request, $transaction);

        $wasCompleted = $transaction->status === TransactionStatus::COMPLETED;

        $result = $this->transactionService->clearOffersFromTransaction(
            $transaction,
            $request->user()->id
        );

        if ($wasCompleted) {
            $this->transactionService->sendAmendedNotification($result, 'Offers removed from transaction');
        }

        return redirect()->back();
    }

    /**
     * Add a payment to a transaction (e.g. refund or additional payment).
     */
    public function addPayment(Request $request, Transaction $transaction): RedirectResponse
    {
        $request->validate([
            'payment_mode_id' => 'required|exists:payment_modes,id',
            'amount' => 'required|numeric',
        ]);

        $this->authorizeVoidPermission($request, $transaction);

        $wasCompleted = $transaction->status === TransactionStatus::COMPLETED;

        $result = $this->transactionService->addPayment(
            $transaction,
            $request->integer('payment_mode_id'),
            (string) $request->input('amount'),
            null,
            $request->user()->id
        );

        if ($wasCompleted) {
            $paymentMode = \App\Models\PaymentMode::find($request->integer('payment_mode_id'));
            $changeDetails = [
                'type' => 'payment_added',
                'method' => $paymentMode?->name ?? 'Unknown',
                'amount' => $request->input('amount'),
            ];
            $this->transactionService->sendAmendedNotification($result, "Payment added: " . $request->input('amount'), $changeDetails);
        }

        return redirect()->back();
    }

    /**
     * Get versions for a transaction (JSON).
     */
    public function versions(Transaction $transaction): JsonResponse
    {
        $versions = $transaction->versions()
            ->with('changedByUser')
            ->orderByDesc('version_number')
            ->get();

        return response()->json($versions);
    }

    /**
     * Get diff between two versions (JSON).
     */
    public function versionDiff(Transaction $transaction, int $version): JsonResponse
    {
        $current = $transaction->versions()
            ->where('version_number', $version)
            ->firstOrFail();

        $previous = $transaction->versions()
            ->where('version_number', $version - 1)
            ->first();

        return response()->json([
            'current' => $current,
            'previous' => $previous,
        ]);
    }

    /**
     * Check if the current user has void/refund permission for the transaction's store.
     */
    private function authorizeVoidPermission(Request $request, Transaction $transaction): void
    {
        $employee = $request->user()->employee;
        $es = $employee?->employeeStores()
            ->where('store_id', $transaction->store_id)
            ->where('active', true)
            ->first();

        if (! $es || ! $es->hasPermission(StorePermissions::VOID_SALES)) {
            abort(403, 'You do not have permission to perform this action for this store.');
        }
    }

    /**
     * Check if the current user can void/refund for the transaction's store.
     */
    private function canVoidTransaction(Request $request, Transaction $transaction): bool
    {
        $employee = $request->user()->employee;
        if (! $employee) {
            return false;
        }

        $es = $employee->employeeStores()
            ->where('store_id', $transaction->store_id)
            ->where('active', true)
            ->first();

        return $es && $es->hasPermission(StorePermissions::VOID_SALES);
    }
}

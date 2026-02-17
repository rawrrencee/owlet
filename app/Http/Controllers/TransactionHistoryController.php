<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Models\Store;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TransactionHistoryController extends Controller
{
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
        if ($request->filled('status')) {
            $query->ofStatus($request->input('status'));
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
            'filters' => $request->only(['search', 'store_id', 'status', 'start_date', 'end_date', 'sort_field', 'sort_order']),
        ]);
    }

    /**
     * Transaction detail page (Inertia).
     */
    public function show(Transaction $transaction): InertiaResponse
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

        return Inertia::render('Transactions/View', [
            'transaction' => $data,
        ]);
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
}

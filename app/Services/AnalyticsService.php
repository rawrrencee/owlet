<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get summary widget data for a date range.
     */
    public function getSalesWidgets(string $from, string $to, ?int $storeId, int $currencyId): array
    {
        $query = $this->baseQuery($from, $to, $storeId, $currencyId);

        $current = $query->selectRaw('
            COALESCE(SUM(total), 0) as total_sales,
            COUNT(*) as transaction_count,
            CASE WHEN COUNT(*) > 0 THEN COALESCE(SUM(total), 0) / COUNT(*) ELSE 0 END as avg_order_value,
            COALESCE(SUM(refund_amount), 0) as total_refunds,
            SUM(CASE WHEN refund_amount > 0 THEN 1 ELSE 0 END) as refund_count
        ')->first();

        // Previous period for comparison
        $fromDate = Carbon::parse($from);
        $toDate = Carbon::parse($to);
        $periodDays = $fromDate->diffInDays($toDate) + 1;
        $prevFrom = $fromDate->copy()->subDays($periodDays)->toDateString();
        $prevTo = $fromDate->copy()->subDay()->toDateString();

        $previous = $this->baseQuery($prevFrom, $prevTo, $storeId, $currencyId)
            ->selectRaw('
                COALESCE(SUM(total), 0) as total_sales,
                COUNT(*) as transaction_count,
                CASE WHEN COUNT(*) > 0 THEN COALESCE(SUM(total), 0) / COUNT(*) ELSE 0 END as avg_order_value
            ')->first();

        return [
            'total_sales' => round((float) $current->total_sales, 2),
            'transaction_count' => (int) $current->transaction_count,
            'avg_order_value' => round((float) $current->avg_order_value, 2),
            'total_refunds' => round((float) $current->total_refunds, 2),
            'refund_count' => (int) $current->refund_count,
            'prev_total_sales' => round((float) $previous->total_sales, 2),
            'prev_transaction_count' => (int) $previous->transaction_count,
            'prev_avg_order_value' => round((float) $previous->avg_order_value, 2),
        ];
    }

    /**
     * Get sales over time grouped by granularity.
     */
    public function getSalesOverTime(string $from, string $to, string $granularity, ?int $storeId, int $currencyId): array
    {
        $dateFormat = match ($granularity) {
            'weekly' => "DATE_FORMAT(checkout_date, '%x-W%v')",
            'monthly' => "DATE_FORMAT(checkout_date, '%Y-%m')",
            default => 'DATE(checkout_date)',
        };

        $labelFormat = match ($granularity) {
            'weekly' => "DATE_FORMAT(checkout_date, '%x-W%v')",
            'monthly' => "DATE_FORMAT(checkout_date, '%b %Y')",
            default => "DATE_FORMAT(checkout_date, '%d %b')",
        };

        $results = $this->baseQuery($from, $to, $storeId, $currencyId)
            ->selectRaw("{$dateFormat} as period, {$labelFormat} as label, COALESCE(SUM(total), 0) as total_sales, COUNT(*) as count")
            ->groupByRaw("{$dateFormat}, {$labelFormat}")
            ->orderByRaw("{$dateFormat}")
            ->get();

        return $results->map(fn ($r) => [
            'period' => $r->period,
            'label' => $r->label,
            'total_sales' => round((float) $r->total_sales, 2),
            'count' => (int) $r->count,
        ])->toArray();
    }

    /**
     * Get sales grouped by store.
     */
    public function getSalesByStore(string $from, string $to, int $currencyId): array
    {
        return Transaction::where('status', TransactionStatus::COMPLETED)
            ->where('currency_id', $currencyId)
            ->whereBetween('checkout_date', [$from, Carbon::parse($to)->endOfDay()])
            ->join('stores', 'transactions.store_id', '=', 'stores.id')
            ->selectRaw('stores.store_name, stores.store_code, COALESCE(SUM(transactions.total), 0) as total_sales, COUNT(*) as count')
            ->groupBy('stores.id', 'stores.store_name', 'stores.store_code')
            ->orderByDesc('total_sales')
            ->get()
            ->map(fn ($r) => [
                'store_name' => $r->store_name,
                'store_code' => $r->store_code,
                'total_sales' => round((float) $r->total_sales, 2),
                'count' => (int) $r->count,
            ])->toArray();
    }

    /**
     * Get top selling products.
     */
    public function getTopProducts(string $from, string $to, ?int $storeId, int $currencyId, int $limit = 10): array
    {
        $query = TransactionItem::join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', TransactionStatus::COMPLETED)
            ->where('transactions.currency_id', $currencyId)
            ->whereBetween('transactions.checkout_date', [$from, Carbon::parse($to)->endOfDay()])
            ->where('transaction_items.is_refunded', false);

        if ($storeId) {
            $query->where('transactions.store_id', $storeId);
        }

        return $query->selectRaw('
                transaction_items.product_id,
                transaction_items.product_name,
                transaction_items.variant_name,
                SUM(transaction_items.quantity) as total_qty,
                COALESCE(SUM(transaction_items.line_total), 0) as total_revenue
            ')
            ->groupBy('transaction_items.product_id', 'transaction_items.product_name', 'transaction_items.variant_name')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'product_id' => $r->product_id,
                'product_name' => $r->product_name,
                'variant_name' => $r->variant_name,
                'total_qty' => (int) $r->total_qty,
                'total_revenue' => round((float) $r->total_revenue, 2),
            ])->toArray();
    }

    /**
     * Get sales by payment method.
     */
    public function getSalesByPaymentMethod(string $from, string $to, ?int $storeId, int $currencyId): array
    {
        $query = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', TransactionStatus::COMPLETED)
            ->where('transactions.currency_id', $currencyId)
            ->whereBetween('transactions.checkout_date', [$from, Carbon::parse($to)->endOfDay()]);

        if ($storeId) {
            $query->where('transactions.store_id', $storeId);
        }

        return $query->selectRaw('
                transaction_payments.payment_mode_name,
                COALESCE(SUM(transaction_payments.amount), 0) as total_amount,
                COUNT(*) as count
            ')
            ->groupBy('transaction_payments.payment_mode_name')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($r) => [
                'payment_method' => $r->payment_mode_name,
                'total_amount' => round((float) $r->total_amount, 2),
                'count' => (int) $r->count,
            ])->toArray();
    }

    /**
     * Get discount breakdown.
     */
    public function getDiscountBreakdown(string $from, string $to, ?int $storeId, int $currencyId): array
    {
        $query = $this->baseQuery($from, $to, $storeId, $currencyId);

        $result = $query->selectRaw('
            COALESCE(SUM(offer_discount), 0) as offer_discount,
            COALESCE(SUM(bundle_discount), 0) as bundle_discount,
            COALESCE(SUM(minimum_spend_discount), 0) as minimum_spend_discount,
            COALESCE(SUM(customer_discount), 0) as customer_discount,
            COALESCE(SUM(manual_discount), 0) as manual_discount
        ')->first();

        return [
            ['label' => 'Offer Discounts', 'amount' => round((float) $result->offer_discount, 2)],
            ['label' => 'Bundle Discounts', 'amount' => round((float) $result->bundle_discount, 2)],
            ['label' => 'Min. Spend Discounts', 'amount' => round((float) $result->minimum_spend_discount, 2)],
            ['label' => 'Customer Discounts', 'amount' => round((float) $result->customer_discount, 2)],
            ['label' => 'Manual Discounts', 'amount' => round((float) $result->manual_discount, 2)],
        ];
    }

    /**
     * Get employee performance data.
     */
    public function getEmployeePerformance(string $from, string $to, ?int $storeId, int $currencyId): array
    {
        $query = Transaction::where('status', TransactionStatus::COMPLETED)
            ->where('currency_id', $currencyId)
            ->whereBetween('checkout_date', [$from, Carbon::parse($to)->endOfDay()])
            ->join('employees', 'transactions.employee_id', '=', 'employees.id');

        if ($storeId) {
            $query->where('transactions.store_id', $storeId);
        }

        return $query->selectRaw("
                employees.id as employee_id,
                CONCAT(employees.first_name, ' ', employees.last_name) as employee_name,
                COALESCE(SUM(transactions.total), 0) as total_sales,
                COUNT(*) as transaction_count,
                CASE WHEN COUNT(*) > 0 THEN COALESCE(SUM(transactions.total), 0) / COUNT(*) ELSE 0 END as avg_order_value
            ")
            ->groupBy('employees.id', 'employees.first_name', 'employees.last_name')
            ->orderByDesc('total_sales')
            ->get()
            ->map(fn ($r) => [
                'employee_id' => $r->employee_id,
                'employee_name' => $r->employee_name,
                'total_sales' => round((float) $r->total_sales, 2),
                'transaction_count' => (int) $r->transaction_count,
                'avg_order_value' => round((float) $r->avg_order_value, 2),
            ])->toArray();
    }

    /**
     * Base query for completed transactions in a date range.
     */
    private function baseQuery(string $from, string $to, ?int $storeId, int $currencyId)
    {
        $query = Transaction::where('status', TransactionStatus::COMPLETED)
            ->where('currency_id', $currencyId)
            ->whereBetween('checkout_date', [$from, Carbon::parse($to)->endOfDay()]);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return $query;
    }
}

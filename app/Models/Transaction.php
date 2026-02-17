<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Models\Concerns\HasAuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasAuditTrail, SoftDeletes;

    protected $fillable = [
        'transaction_number',
        'store_id',
        'employee_id',
        'customer_id',
        'currency_id',
        'status',
        'checkout_date',
        'subtotal',
        'offer_discount',
        'bundle_discount',
        'minimum_spend_discount',
        'customer_discount',
        'manual_discount',
        'tax_percentage',
        'tax_inclusive',
        'tax_amount',
        'total',
        'amount_paid',
        'refund_amount',
        'balance_due',
        'change_amount',
        'comments',
        'bundle_offer_id',
        'bundle_offer_name',
        'minimum_spend_offer_id',
        'minimum_spend_offer_name',
        'customer_discount_percentage',
        'manual_discount_type',
        'manual_discount_value',
        'version_count',
        'created_by',
        'updated_by',
        'previous_updated_by',
        'previous_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
            'checkout_date' => 'datetime',
            'subtotal' => 'decimal:4',
            'offer_discount' => 'decimal:4',
            'bundle_discount' => 'decimal:4',
            'minimum_spend_discount' => 'decimal:4',
            'customer_discount' => 'decimal:4',
            'manual_discount' => 'decimal:4',
            'tax_percentage' => 'decimal:2',
            'tax_inclusive' => 'boolean',
            'tax_amount' => 'decimal:4',
            'total' => 'decimal:4',
            'amount_paid' => 'decimal:4',
            'refund_amount' => 'decimal:4',
            'balance_due' => 'decimal:4',
            'change_amount' => 'decimal:4',
            'customer_discount_percentage' => 'decimal:2',
            'manual_discount_value' => 'decimal:4',
            'version_count' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TransactionPayment::class)->orderBy('row_number');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(TransactionVersion::class)->orderBy('version_number');
    }

    public function bundleOffer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'bundle_offer_id');
    }

    public function minimumSpendOffer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'minimum_spend_offer_id');
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    // Scopes

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeOfStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::COMPLETED);
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::SUSPENDED);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::DRAFT);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('transaction_number', 'like', "%{$search}%");
        });
    }

    public function scopeDateRange(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        if ($startDate) {
            $query->whereDate('checkout_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('checkout_date', '<=', $endDate);
        }

        return $query;
    }

    public static function generateTransactionNumber(Store $store): string
    {
        $date = now()->format('Ymd');
        $prefix = "TXN-{$store->store_code}-{$date}-";

        $latest = static::withTrashed()
            ->where('transaction_number', 'like', "{$prefix}%")
            ->orderByDesc('transaction_number')
            ->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->transaction_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}

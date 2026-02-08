<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Models\Concerns\HasAuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasAuditTrail, SoftDeletes;

    protected $fillable = [
        'order_number',
        'supplier_id',
        'store_id',
        'status',
        'notes',
        'submitted_at',
        'submitted_by',
        'resolved_at',
        'resolved_by',
        'rejection_reason',
        'created_by',
        'updated_by',
        'previous_updated_by',
        'previous_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'submitted_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PurchaseOrderStatus::DRAFT);
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->where('status', PurchaseOrderStatus::SUBMITTED);
    }

    public function scopeForSupplier(Builder $query, int $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeDateRange(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query;
    }

    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "PO-{$date}-";

        $latest = static::withTrashed()
            ->where('order_number', 'like', "{$prefix}%")
            ->orderByDesc('order_number')
            ->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->order_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}

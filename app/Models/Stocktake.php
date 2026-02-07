<?php

namespace App\Models;

use App\Enums\StocktakeStatus;
use App\Models\Concerns\HasAuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stocktake extends Model
{
    use HasAuditTrail;

    protected $fillable = [
        'employee_id',
        'store_id',
        'status',
        'has_issues',
        'submitted_at',
        'notes',
        'created_by',
        'updated_by',
        'previous_updated_by',
        'previous_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StocktakeStatus::class,
            'has_issues' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StocktakeItem::class);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->where('status', StocktakeStatus::SUBMITTED);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', StocktakeStatus::IN_PROGRESS);
    }

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeDateRange(Builder $query, ?string $start, ?string $end): Builder
    {
        if ($start) {
            $query->where('submitted_at', '>=', $start);
        }
        if ($end) {
            $query->where('submitted_at', '<=', $end . ' 23:59:59');
        }

        return $query;
    }

    /**
     * Recompute has_issues from items.
     */
    public function recomputeHasIssues(): void
    {
        $hasIssues = $this->items()->where('has_discrepancy', true)->exists();
        $this->update(['has_issues' => $hasIssues]);
    }
}

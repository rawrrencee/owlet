<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Timecard extends Model
{
    use HasFactory;

    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_EXPIRED = 'EXPIRED';

    protected $fillable = [
        'employee_id',
        'store_id',
        'status',
        'is_incomplete',
        'is_inaccurate',
        'start_date',
        'end_date',
        'user_provided_end_date',
        'hours_worked',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'user_provided_end_date' => 'datetime',
            'hours_worked' => 'decimal:2',
            'is_incomplete' => 'boolean',
            'is_inaccurate' => 'boolean',
        ];
    }

    // Relationships

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(TimecardDetail::class);
    }

    public function workDetails(): HasMany
    {
        return $this->details()->where('type', TimecardDetail::TYPE_WORK);
    }

    public function breakDetails(): HasMany
    {
        return $this->details()->where('type', TimecardDetail::TYPE_BREAK);
    }

    public function createdByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function updatedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'updated_by');
    }

    // Scopes

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->where('is_incomplete', true);
    }

    public function scopeNeedsResolution(Builder $query): Builder
    {
        return $query->where('is_incomplete', true)
            ->whereNull('user_provided_end_date');
    }

    public function scopeForDate(Builder $query, CarbonInterface $date): Builder
    {
        return $query->whereDate('start_date', $date);
    }

    public function scopeForDateRange(Builder $query, CarbonInterface $startDate, CarbonInterface $endDate): Builder
    {
        return $query->whereDate('start_date', '>=', $startDate)
            ->whereDate('start_date', '<=', $endDate);
    }

    public function scopeForMonth(Builder $query, CarbonInterface $month): Builder
    {
        return $query->whereYear('start_date', $month->year)
            ->whereMonth('start_date', $month->month);
    }

    // Accessors

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_EXPIRED => 'Expired',
            default => $this->status,
        };
    }

    public function getIsInProgressAttribute(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Get the current open detail (work or break).
     */
    public function getCurrentDetail(): ?TimecardDetail
    {
        return $this->details()
            ->whereNull('end_date')
            ->orderBy('start_date', 'desc')
            ->first();
    }

    /**
     * Check if currently on break.
     */
    public function isOnBreak(): bool
    {
        $currentDetail = $this->getCurrentDetail();

        return $currentDetail && $currentDetail->type === TimecardDetail::TYPE_BREAK;
    }
}

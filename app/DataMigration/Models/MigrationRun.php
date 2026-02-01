<?php

namespace App\DataMigration\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MigrationRun extends Model
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'model_type',
        'started_at',
        'completed_at',
        'status',
        'total_records',
        'migrated_count',
        'failed_count',
        'skipped_count',
        'error_message',
        'initiated_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_records' => 'integer',
            'migrated_count' => 'integer',
            'failed_count' => 'integer',
            'skipped_count' => 'integer',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MigrationLog::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    public function markCancelled(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completed_at' => now(),
        ]);
    }

    public function incrementMigrated(): void
    {
        $this->increment('migrated_count');
    }

    public function incrementFailed(): void
    {
        $this->increment('failed_count');
    }

    public function incrementSkipped(): void
    {
        $this->increment('skipped_count');
    }

    public function getProgressPercentage(): float
    {
        if ($this->total_records === 0) {
            return 100;
        }

        $processed = $this->migrated_count + $this->failed_count + $this->skipped_count;

        return round(($processed / $this->total_records) * 100, 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RUNNING => 'Running',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => $this->status,
        };
    }
}

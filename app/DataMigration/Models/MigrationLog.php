<?php

namespace App\DataMigration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MigrationLog extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'model_type',
        'legacy_id',
        'owlet_id',
        'migration_run_id',
        'status',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'legacy_id' => 'integer',
            'owlet_id' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function migrationRun(): BelongsTo
    {
        return $this->belongsTo(MigrationRun::class);
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    public function markSuccess(int $owletId, ?array $metadata = null): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'owlet_id' => $owletId,
            'error_message' => null,
            'metadata' => $metadata,
        ]);
    }

    public function markFailed(string $errorMessage, ?array $metadata = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
        ]);
    }

    public function markSkipped(string $reason, ?array $metadata = null): void
    {
        $this->update([
            'status' => self::STATUS_SKIPPED,
            'error_message' => $reason,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Find or create a log entry for a legacy record.
     */
    public static function findOrCreateForLegacy(string $modelType, int $legacyId, int $migrationRunId): self
    {
        return self::firstOrCreate(
            ['model_type' => $modelType, 'legacy_id' => $legacyId],
            ['migration_run_id' => $migrationRunId, 'status' => self::STATUS_PENDING]
        );
    }

    /**
     * Get the Owlet model ID if migration was successful.
     */
    public function getOwletId(): ?int
    {
        return $this->isSuccess() ? $this->owlet_id : null;
    }
}

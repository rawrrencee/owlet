<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasAuditTrail
{
    public function initializeHasAuditTrail(): void
    {
        $this->mergeCasts([
            'previous_updated_at' => 'datetime',
        ]);
    }

    public static function bootHasAuditTrail(): void
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by ??= auth()->id();
                $model->updated_by ??= auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->previous_updated_by = $model->getOriginal('updated_by');
                $model->previous_updated_at = $model->getOriginal('updated_at');
                $model->updated_by = auth()->id();
            }
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function previousUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'previous_updated_by');
    }
}

<?php

namespace App\Models;

use App\Enums\NotificationEventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRecipient extends Model
{
    protected $fillable = [
        'event_type',
        'store_id',
        'email',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => NotificationEventType::class,
            'is_active' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForEventType(Builder $query, NotificationEventType $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }
}

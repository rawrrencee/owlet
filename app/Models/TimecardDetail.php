<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimecardDetail extends Model
{
    use HasFactory;

    public const TYPE_WORK = 'WORK';

    public const TYPE_BREAK = 'BREAK';

    protected $fillable = [
        'timecard_id',
        'type',
        'start_date',
        'end_date',
        'hours',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'hours' => 'decimal:2',
        ];
    }

    // Relationships

    public function timecard(): BelongsTo
    {
        return $this->belongsTo(Timecard::class);
    }

    // Accessors

    public function getIsWorkAttribute(): bool
    {
        return $this->type === self::TYPE_WORK;
    }

    public function getIsBreakAttribute(): bool
    {
        return $this->type === self::TYPE_BREAK;
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->end_date === null;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_WORK => 'Work',
            self::TYPE_BREAK => 'Break',
            default => $this->type,
        };
    }
}

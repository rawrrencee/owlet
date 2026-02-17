<?php

namespace App\Models;

use App\Enums\TransactionChangeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionVersion extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'transaction_id',
        'version_number',
        'change_type',
        'changed_by',
        'change_summary',
        'snapshot_items',
        'snapshot_payments',
        'snapshot_totals',
        'diff_data',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'change_type' => TransactionChangeType::class,
            'snapshot_items' => 'array',
            'snapshot_payments' => 'array',
            'snapshot_totals' => 'array',
            'diff_data' => 'array',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

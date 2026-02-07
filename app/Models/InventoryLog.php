<?php

namespace App\Models;

use App\Constants\InventoryActivityCodes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    protected $fillable = [
        'product_id',
        'store_id',
        'stocktake_id',
        'activity_code',
        'quantity_in',
        'quantity_out',
        'current_quantity',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_in' => 'integer',
            'quantity_out' => 'integer',
            'current_quantity' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function stocktake(): BelongsTo
    {
        return $this->belongsTo(Stocktake::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivityLabelAttribute(): string
    {
        return InventoryActivityCodes::label($this->activity_code);
    }
}

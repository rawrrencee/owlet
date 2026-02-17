<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractLeaveEntitlement extends Model
{
    protected $fillable = [
        'employee_contract_id',
        'leave_type_id',
        'entitled_days',
        'taken_days',
    ];

    protected function casts(): array
    {
        return [
            'entitled_days' => 'decimal:1',
            'taken_days' => 'decimal:1',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(EmployeeContract::class, 'employee_contract_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function getRemainingDaysAttribute(): float
    {
        return max(0, (float) $this->entitled_days - (float) $this->taken_days);
    }
}

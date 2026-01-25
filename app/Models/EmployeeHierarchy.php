<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeHierarchy extends Model
{
    protected $fillable = [
        'manager_id',
        'subordinate_id',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinate(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'subordinate_id');
    }
}

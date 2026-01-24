<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCompany extends Model
{
    protected $table = 'employee_companies';

    protected $fillable = [
        'employee_id',
        'company_id',
        'designation_id',
        'levy_amount',
        'status',
        'include_shg_donations',
        'commencement_date',
        'left_date',
    ];

    protected function casts(): array
    {
        return [
            'levy_amount' => 'decimal:4',
            'commencement_date' => 'date',
            'left_date' => 'date',
            'include_shg_donations' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function isActive(): bool
    {
        return $this->left_date === null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'FT' => 'Full Time',
            'PT' => 'Part Time',
            'CT' => 'Contract',
            'CA' => 'Casual',
            default => $this->status,
        };
    }
}

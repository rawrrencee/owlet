<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'chinese_name',
        'employee_number',
        'nric',
        'phone',
        'mobile',
        'address_1',
        'address_2',
        'city',
        'state',
        'postal_code',
        'country',
        'date_of_birth',
        'gender',
        'race',
        'nationality',
        'residency_status',
        'pr_conversion_date',
        'emergency_name',
        'emergency_relationship',
        'emergency_contact',
        'emergency_address_1',
        'emergency_address_2',
        'bank_name',
        'bank_account_number',
        'hire_date',
        'termination_date',
        'notes',
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'termination_date' => 'date',
            'pr_conversion_date' => 'date',
        ];
    }

    public function isActive(): bool
    {
        return $this->termination_date === null;
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}

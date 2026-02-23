<?php

namespace App\Models;

use App\Enums\EmployeeRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeRequest extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'chinese_name',
        'email',
        'phone',
        'mobile',
        'date_of_birth',
        'gender',
        'race',
        'nric',
        'nationality',
        'nationality_id',
        'residency_status',
        'address_1',
        'address_2',
        'city',
        'state',
        'postal_code',
        'country_id',
        'emergency_name',
        'emergency_relationship',
        'emergency_contact',
        'emergency_address_1',
        'emergency_address_2',
        'bank_name',
        'bank_account_number',
        'notes',
        'profile_picture',
        'status',
        'rejection_reason',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => EmployeeRequestStatus::class,
            'date_of_birth' => 'date',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function nationalityCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'nationality_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}

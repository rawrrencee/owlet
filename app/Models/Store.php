<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_name',
        'store_code',
        'company_id',
        'address_1',
        'address_2',
        'email',
        'phone_number',
        'mobile_number',
        'website',
        'active',
        'include_tax',
        'tax_percentage',
        'logo',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'include_tax' => 'boolean',
            'tax_percentage' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_stores')
            ->withPivot(['active', 'permissions'])
            ->withTimestamps();
    }

    public function employeeStores(): HasMany
    {
        return $this->hasMany(EmployeeStore::class);
    }

    public function activeEmployees(): BelongsToMany
    {
        return $this->employees()->wherePivot('active', true);
    }

    /**
     * Mutator to ensure store_code is always uppercase.
     */
    public function setStoreCodeAttribute(string $value): void
    {
        $this->attributes['store_code'] = strtoupper($value);
    }
}

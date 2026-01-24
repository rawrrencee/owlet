<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_name',
        'address_1',
        'address_2',
        'email',
        'phone_number',
        'mobile_number',
        'website',
        'logo',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_companies')
            ->withPivot(['designation_id', 'levy_amount', 'status', 'include_shg_donations', 'commencement_date', 'left_date'])
            ->withTimestamps();
    }

    public function employeeCompanies(): HasMany
    {
        return $this->hasMany(EmployeeCompany::class);
    }

    public function activeEmployees(): BelongsToMany
    {
        return $this->employees()->whereNull('employee_companies.left_date');
    }
}

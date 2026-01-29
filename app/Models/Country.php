<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'code_3',
        'nationality_name',
        'phone_code',
        'active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'country_id');
    }

    public function employeesByNationality(): HasMany
    {
        return $this->hasMany(Employee::class, 'nationality_id');
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'country_id');
    }

    public function customersByNationality(): HasMany
    {
        return $this->hasMany(Customer::class, 'nationality_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

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
        'profile_picture',
        'external_avatar_url',
        'pending_email',
        'pending_role',
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

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'employee_companies')
            ->withPivot(['designation_id', 'levy_amount', 'status', 'include_shg_donations', 'commencement_date', 'left_date'])
            ->withTimestamps();
    }

    public function employeeCompanies(): HasMany
    {
        return $this->hasMany(EmployeeCompany::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }

    public function activeContracts(): HasMany
    {
        return $this->contracts()
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    public function insurances(): HasMany
    {
        return $this->hasMany(EmployeeInsurance::class);
    }

    public function activeInsurances(): HasMany
    {
        return $this->insurances()
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    public function activeCompanies(): BelongsToMany
    {
        return $this->companies()->whereNull('employee_companies.left_date');
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'employee_stores')
            ->withPivot(['active', 'permissions'])
            ->withTimestamps();
    }

    public function employeeStores(): HasMany
    {
        return $this->hasMany(EmployeeStore::class);
    }

    public function activeStores(): BelongsToMany
    {
        return $this->stores()->wherePivot('active', true);
    }

    /**
     * Check if the employee has a specific permission for a store.
     */
    public function hasStorePermission(int $storeId, string $permission): bool
    {
        $employeeStore = $this->employeeStores()
            ->where('store_id', $storeId)
            ->where('active', true)
            ->first();

        return $employeeStore?->hasPermission($permission) ?? false;
    }

    /**
     * Get all permissions for a specific store.
     *
     * @return array<string>
     */
    public function getStorePermissions(int $storeId): array
    {
        $employeeStore = $this->employeeStores()
            ->where('store_id', $storeId)
            ->where('active', true)
            ->first();

        return $employeeStore?->permissions ?? [];
    }

    /**
     * Get all stores where the employee has a specific permission.
     */
    public function storesWithPermission(string $permission): Collection
    {
        return $this->employeeStores()
            ->where('active', true)
            ->get()
            ->filter(fn (EmployeeStore $es) => $es->hasPermission($permission))
            ->map(fn (EmployeeStore $es) => $es->store);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the profile picture URL with fallbacks.
     *
     * Priority: local profile_picture > external_avatar_url > User.avatar
     */
    public function getProfilePictureUrl(): ?string
    {
        // 1. Local uploaded profile picture takes priority
        if ($this->profile_picture) {
            return route('users.profile-picture', $this->id);
        }

        // 2. External avatar URL from WorkOS (stored on Employee)
        if ($this->external_avatar_url) {
            return $this->external_avatar_url;
        }

        // 3. Fall back to User's avatar (for users who logged in before migration)
        if ($this->relationLoaded('user') && $this->user?->avatar) {
            return $this->user->avatar;
        }

        return null;
    }
}

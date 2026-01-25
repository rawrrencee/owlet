<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'workos_id',
        'avatar',
        'role',
        'employee_id',
        'customer_id',
        'is_active',
    ];

    protected $hidden = [
        'workos_id',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the avatar URL, preferring locally uploaded profile picture.
     *
     * Priority: Employee's profile_picture > Employee's external_avatar_url > User's avatar
     */
    public function getAvatarAttribute(?string $value): ?string
    {
        if ($this->employee_id && $this->relationLoaded('employee') && $this->employee) {
            if ($this->employee->profile_picture) {
                return route('users.profile-picture', $this->employee->id);
            }

            if ($this->employee->external_avatar_url) {
                return $this->employee->external_avatar_url;
            }
        }

        return $value;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isEmployee(): bool
    {
        return $this->employee_id !== null;
    }

    public function isCustomer(): bool
    {
        return $this->customer_id !== null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
}

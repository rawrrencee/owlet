<?php

namespace App\Http\Requests;

use App\Enums\EmployeeRequestStatus;
use App\Models\EmployeeRequest;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SubmitEmployeeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'chinese_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    // Check existing users
                    if (User::where('email', $value)->exists()) {
                        $fail('An account with this email already exists.');
                    }
                    // Check pending requests
                    if (EmployeeRequest::where('email', $value)
                        ->where('status', EmployeeRequestStatus::PENDING)
                        ->exists()) {
                        $fail('A pending application with this email already exists.');
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'race' => ['nullable', 'string', 'max:255'],
            'nric' => ['nullable', 'string', 'max:50'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'nationality_id' => ['nullable', 'integer', 'exists:countries,id'],
            'residency_status' => ['nullable', 'string', 'max:255'],
            'address_1' => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'emergency_name' => ['nullable', 'string', 'max:255'],
            'emergency_relationship' => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:50'],
            'emergency_address_1' => ['nullable', 'string', 'max:255'],
            'emergency_address_2' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'profile_picture' => ['nullable', 'image', 'max:5120'],
        ];
    }
}

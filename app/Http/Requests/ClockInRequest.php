<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ClockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->employee !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_id' => ['required', 'exists:stores,id'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $storeId = $this->input('store_id');
            $employee = Auth::user()->employee;

            if (! $employee) {
                $validator->errors()->add('store_id', 'You must have an employee record to clock in.');

                return;
            }

            // Check if employee is assigned to this store
            $isAssigned = $employee->activeStores()->where('stores.id', $storeId)->exists();

            if (! $isAssigned) {
                $validator->errors()->add('store_id', 'You are not assigned to this store.');
            }

            // Check if employee already has an active timecard today
            $hasActiveTimecard = $employee->timecards()
                ->where('status', 'IN_PROGRESS')
                ->whereDate('start_date', today())
                ->exists();

            if ($hasActiveTimecard) {
                $validator->errors()->add('store_id', 'You already have an active timecard for today. Please clock out first.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'store_id.required' => 'Please select a store to clock in at.',
            'store_id.exists' => 'The selected store is invalid.',
        ];
    }
}

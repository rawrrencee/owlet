<?php

namespace App\Http\Requests;

use App\Models\Timecard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTimecardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'store_id' => ['required', 'exists:stores,id'],
            'date' => ['required', 'date'],
            'status' => ['required', Rule::in([
                Timecard::STATUS_IN_PROGRESS,
                Timecard::STATUS_COMPLETED,
                Timecard::STATUS_EXPIRED,
            ])],
            // Optional time entries
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'breaks' => ['nullable', 'array'],
            'breaks.*.start_time' => ['required_with:breaks', 'date'],
            'breaks.*.end_time' => ['required_with:breaks', 'date', 'after:breaks.*.start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Please select an employee.',
            'employee_id.exists' => 'The selected employee is invalid.',
            'store_id.required' => 'Please select a store.',
            'store_id.exists' => 'The selected store is invalid.',
            'date.required' => 'Please enter a date.',
            'date.date' => 'Please enter a valid date.',
            'status.required' => 'Please select a status.',
            'status.in' => 'The selected status is invalid.',
            'end_time.after' => 'End time must be after start time.',
            'breaks.*.end_time.after' => 'Break end time must be after break start time.',
        ];
    }
}

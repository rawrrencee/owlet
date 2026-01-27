<?php

namespace App\Http\Requests;

use App\Models\TimecardDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTimecardDetailRequest extends FormRequest
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
            'type' => ['required', Rule::in([
                TimecardDetail::TYPE_WORK,
                TimecardDetail::TYPE_BREAK,
            ])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Please select a type.',
            'type.in' => 'The selected type is invalid. Must be WORK or BREAK.',
            'start_date.required' => 'Please enter a start time.',
            'start_date.date' => 'Please enter a valid start time.',
            'end_date.date' => 'Please enter a valid end time.',
            'end_date.after' => 'End time must be after start time.',
        ];
    }
}

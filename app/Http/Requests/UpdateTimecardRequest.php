<?php

namespace App\Http\Requests;

use App\Models\Timecard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTimecardRequest extends FormRequest
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
            'store_id' => ['sometimes', 'exists:stores,id'],
            'status' => ['sometimes', Rule::in([
                Timecard::STATUS_IN_PROGRESS,
                Timecard::STATUS_COMPLETED,
                Timecard::STATUS_EXPIRED,
            ])],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_id.exists' => 'The selected store is invalid.',
            'status.in' => 'The selected status is invalid.',
            'start_date.date' => 'Please enter a valid start date.',
            'end_date.date' => 'Please enter a valid end date.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
        ];
    }
}

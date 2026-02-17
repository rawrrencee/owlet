<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeContractRequest extends FormRequest
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
            'company_id' => ['nullable', 'exists:companies,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'salary_amount' => ['required', 'numeric', 'min:0'],
            'entitlements' => ['nullable', 'array'],
            'entitlements.*.leave_type_id' => ['required_with:entitlements', 'exists:leave_types,id'],
            'entitlements.*.entitled_days' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'entitlements.*.taken_days' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'external_document_url' => ['nullable', 'url', 'max:255'],
            'comments' => ['nullable', 'string', 'max:65535'],
        ];
    }
}

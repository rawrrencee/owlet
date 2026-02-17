<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentContractRequest extends FormRequest
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
            'company_id' => ['nullable', 'exists:companies,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'salary_amount' => ['required', 'numeric', 'min:0'],
            'entitlements' => ['nullable', 'array'],
            'entitlements.*.leave_type_id' => ['required', 'exists:leave_types,id'],
            'entitlements.*.entitled_days' => ['required', 'numeric', 'min:0'],
            'entitlements.*.taken_days' => ['nullable', 'numeric', 'min:0'],
            'external_document_url' => ['nullable', 'url', 'max:255'],
            'document' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,gif,doc,docx'],
            'comments' => ['nullable', 'string', 'max:65535'],
        ];
    }
}

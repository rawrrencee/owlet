<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeCompanyRequest extends FormRequest
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
            'company_id' => ['required', 'exists:companies,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'levy_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:FT,PT,CT,CA'],
            'include_shg_donations' => ['boolean'],
            'commencement_date' => ['required', 'date'],
            'left_date' => ['nullable', 'date', 'after_or_equal:commencement_date'],
        ];
    }
}

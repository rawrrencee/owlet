<?php

namespace App\Http\Requests;

use App\Models\EmployeeCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCompanyEmployeeRequest extends FormRequest
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
            'designation_id' => ['nullable', 'exists:designations,id'],
            'levy_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:FT,PT,CT,CA'],
            'include_shg_donations' => ['boolean'],
            'commencement_date' => ['required', 'date'],
            'left_date' => ['nullable', 'date', 'after_or_equal:commencement_date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $employeeId = $this->input('employee_id');
            $companyId = $this->route('company')->id;

            // Check if an active assignment already exists
            $existingAssignment = EmployeeCompany::where('employee_id', $employeeId)
                ->where('company_id', $companyId)
                ->whereNull('left_date')
                ->exists();

            if ($existingAssignment) {
                $validator->errors()->add('employee_id', 'This employee already has an active assignment to this company.');
            }
        });
    }
}

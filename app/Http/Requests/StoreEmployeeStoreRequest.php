<?php

namespace App\Http\Requests;

use App\Constants\StorePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeStoreRequest extends FormRequest
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
        // Support both routes: /users/{employee}/stores and /stores/{store}/employees
        $employeeId = $this->route('employee')?->id;
        $storeId = $this->route('store')?->id;

        $rules = [
            'active' => ['boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(StorePermissions::keys())],
        ];

        if ($employeeId) {
            // Adding store to employee
            $rules['store_id'] = [
                'required',
                'exists:stores,id',
                Rule::unique('employee_stores', 'store_id')
                    ->where('employee_id', $employeeId),
            ];
        } else {
            // Adding employee to store
            $rules['employee_id'] = [
                'required',
                'exists:employees,id',
                Rule::unique('employee_stores', 'employee_id')
                    ->where('store_id', $storeId),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'store_id.unique' => 'This employee is already assigned to this store.',
            'employee_id.unique' => 'This employee is already assigned to this store.',
        ];
    }
}

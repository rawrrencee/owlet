<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStoreRequest extends FormRequest
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
            'store_name' => ['required', 'string', 'max:255'],
            'store_code' => [
                'required',
                'string',
                'max:4',
                'unique:stores,store_code',
                Rule::notIn(['LNF', 'WHS', 'lnf', 'whs']),
            ],
            'company_id' => ['nullable', 'exists:companies,id'],
            'address_1' => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'mobile_number' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'active' => ['boolean'],
            'include_tax' => ['boolean'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'logo' => ['nullable', 'image', 'max:5120'], // 5MB max
        ];
    }

    public function messages(): array
    {
        return [
            'store_code.not_in' => 'The store code :input is reserved and cannot be used.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('store_code')) {
            $this->merge([
                'store_code' => strtoupper($this->store_code),
            ]);
        }
    }
}

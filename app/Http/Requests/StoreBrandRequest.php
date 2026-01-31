<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
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
            'brand_name' => ['required', 'string', 'max:255'],
            'brand_code' => ['required', 'string', 'max:4', 'unique:brands,brand_code'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'address_1' => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'mobile_number' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'logo' => ['nullable', 'image', 'max:5120'], // 5MB max
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('brand_code')) {
            $this->merge([
                'brand_code' => strtoupper($this->brand_code),
            ]);
        }
    }
}

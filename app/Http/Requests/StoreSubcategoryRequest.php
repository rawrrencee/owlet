<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubcategoryRequest extends FormRequest
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
        $categoryId = $this->route('category')?->id ?? $this->route('category');

        return [
            'subcategory_name' => ['required', 'string', 'max:255'],
            'subcategory_code' => [
                'required',
                'string',
                'max:4',
                Rule::unique('subcategories', 'subcategory_code')
                    ->where('category_id', $categoryId),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('subcategory_code')) {
            $this->merge([
                'subcategory_code' => strtoupper($this->subcategory_code),
            ]);
        }
    }
}

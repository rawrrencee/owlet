<?php

namespace App\Http\Requests;

use App\Enums\WeightUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            // Basic product fields
            'product_name' => ['required', 'string', 'max:255'],
            'product_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'product_number')->whereNull('deleted_at'),
            ],
            'barcode' => ['nullable', 'string', 'max:255'],
            'brand_id' => ['required', 'exists:brands,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['required', 'exists:subcategories,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'supplier_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50', 'distinct'],
            'cost_price_remarks' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'weight_unit' => ['required', Rule::enum(WeightUnit::class)],
            'image' => ['nullable', 'image', 'max:5120'], // 5MB max
            'is_active' => ['boolean'],

            // Base prices (per currency)
            'prices' => ['nullable', 'array'],
            'prices.*.currency_id' => ['required', 'exists:currencies,id'],
            'prices.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'prices.*.unit_price' => ['nullable', 'numeric', 'min:0'],

            // Store assignments
            'stores' => ['nullable', 'array'],
            'stores.*.store_id' => ['required', 'exists:stores,id'],
            'stores.*.quantity' => ['nullable', 'integer', 'min:0'],
            'stores.*.is_active' => ['boolean'],
            'stores.*.prices' => ['nullable', 'array'],
            'stores.*.prices.*.currency_id' => ['required', 'exists:currencies,id'],
            'stores.*.prices.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'stores.*.prices.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('product_number')) {
            $this->merge([
                'product_number' => strtoupper(trim($this->product_number)),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'product_number.unique' => 'This product number is already in use.',
            'prices.*.currency_id.required' => 'Currency is required for each price.',
            'prices.*.currency_id.exists' => 'Selected currency is invalid.',
            'stores.*.store_id.required' => 'Store is required for each store assignment.',
            'stores.*.store_id.exists' => 'Selected store is invalid.',
            'stores.*.prices.*.currency_id.required' => 'Currency is required for each store price.',
            'stores.*.prices.*.currency_id.exists' => 'Selected currency for store price is invalid.',
        ];
    }
}

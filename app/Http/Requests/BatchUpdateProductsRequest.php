<?php

namespace App\Http\Requests;

use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BatchUpdateProductsRequest extends FormRequest
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
            // Product IDs to update
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],

            // Classification - Brand
            'apply_brand' => ['boolean'],
            'brand_id' => ['nullable', 'required_if:apply_brand,true', 'exists:brands,id'],

            // Classification - Category
            'apply_category' => ['boolean'],
            'category_id' => ['nullable', 'required_if:apply_category,true', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'required_if:apply_category,true', 'exists:subcategories,id'],

            // Classification - Supplier
            'apply_supplier' => ['boolean'],
            'supplier_id' => ['nullable', 'required_if:apply_supplier,true', 'exists:suppliers,id'],

            // Status
            'apply_status' => ['boolean'],
            'is_active' => ['boolean'],

            // Tags
            'apply_tags' => ['boolean'],
            'tags_to_add' => ['nullable', 'array'],
            'tags_to_add.*' => ['string', 'max:50'],
            'tags_to_remove' => ['nullable', 'array'],
            'tags_to_remove.*' => ['string', 'max:50'],

            // Prices
            'apply_prices' => ['boolean'],
            'price_mode' => ['nullable', 'required_if:apply_prices,true', 'in:fixed,percentage'],
            'price_adjustments' => ['nullable', 'array'],
            'price_adjustments.*.currency_id' => ['required', 'exists:currencies,id'],
            'price_adjustments.*.cost_price' => ['nullable', 'numeric'],
            'price_adjustments.*.unit_price' => ['nullable', 'numeric'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Validate subcategory belongs to the selected category
            if ($this->boolean('apply_category') && $this->filled('category_id') && $this->filled('subcategory_id')) {
                $subcategory = \App\Models\Subcategory::find($this->input('subcategory_id'));
                if ($subcategory && $subcategory->category_id !== (int) $this->input('category_id')) {
                    $validator->errors()->add(
                        'subcategory_id',
                        'The selected subcategory does not belong to the selected category.'
                    );
                }
            }

            if (! $this->boolean('apply_prices')) {
                return;
            }

            $adjustments = $this->input('price_adjustments', []);

            foreach ($adjustments as $index => $adjustment) {
                $hasCostPrice = isset($adjustment['cost_price']) && $adjustment['cost_price'] !== null && $adjustment['cost_price'] !== '';
                $hasUnitPrice = isset($adjustment['unit_price']) && $adjustment['unit_price'] !== null && $adjustment['unit_price'] !== '';

                // If either is provided, both must be provided
                if ($hasCostPrice !== $hasUnitPrice) {
                    $currencyCode = $this->getCurrencyCode($adjustment['currency_id'] ?? null);
                    $missingField = $hasCostPrice ? 'unit price' : 'cost price';

                    $validator->errors()->add(
                        "price_adjustments.{$index}",
                        "Both cost price and unit price are required for {$currencyCode}. Missing: {$missingField}."
                    );
                }
            }
        });
    }

    /**
     * Get currency code for error messages.
     */
    private function getCurrencyCode(?int $currencyId): string
    {
        if (! $currencyId) {
            return 'unknown currency';
        }

        return Currency::find($currencyId)?->code ?? 'unknown currency';
    }

    public function messages(): array
    {
        return [
            'product_ids.required' => 'Please select at least one product.',
            'product_ids.min' => 'Please select at least one product.',
            'product_ids.*.exists' => 'One or more selected products do not exist.',
            'brand_id.required_if' => 'Brand is required when applying brand changes.',
            'category_id.required_if' => 'Category is required when applying category changes.',
            'subcategory_id.required_if' => 'Subcategory is required when applying category changes.',
            'supplier_id.required_if' => 'Supplier is required when applying supplier changes.',
            'price_mode.required_if' => 'Price mode is required when applying price changes.',
            'price_adjustments.*.currency_id.required' => 'Currency is required for each price adjustment.',
            'price_adjustments.*.currency_id.exists' => 'Selected currency is invalid.',
        ];
    }
}

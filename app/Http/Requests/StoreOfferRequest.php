<?php

namespace App\Http\Requests;

use App\Enums\BundleMode;
use App\Enums\DiscountType;
use App\Enums\OfferType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('type');
        $discountType = $this->input('discount_type');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', 'unique:offers,code'],
            'type' => ['required', Rule::in(array_column(OfferType::cases(), 'value'))],
            'discount_type' => ['required', Rule::in(array_column(DiscountType::cases(), 'value'))],
            'discount_percentage' => [
                'nullable',
                'required_if:discount_type,percentage',
                'numeric',
                'min:0.01',
                'max:100',
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_combinable' => ['boolean'],
            'priority' => ['integer', 'min:0'],
            'apply_to_all_stores' => ['boolean'],
            'store_ids' => ['array'],
            'store_ids.*' => ['exists:stores,id'],

            // Per-currency amounts
            'amounts' => ['nullable', 'array'],
            'amounts.*.currency_id' => ['required_with:amounts', 'exists:currencies,id'],
            'amounts.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'amounts.*.max_discount_amount' => ['nullable', 'numeric', 'min:0'],
        ];

        // Type-specific validations
        if ($type === 'product') {
            $rules['product_ids'] = ['required', 'array', 'min:1'];
            $rules['product_ids.*'] = ['exists:products,id'];
        }

        if ($type === 'bundle') {
            $rules['bundle_mode'] = ['required', Rule::in(array_column(BundleMode::cases(), 'value'))];
            $rules['bundle_items'] = ['required', 'array', 'min:1'];
            $rules['bundle_items.*.product_id'] = ['nullable', 'required_without_all:bundle_items.*.category_id,bundle_items.*.subcategory_id', 'exists:products,id'];
            $rules['bundle_items.*.category_id'] = ['nullable', 'required_without_all:bundle_items.*.product_id,bundle_items.*.subcategory_id', 'exists:categories,id'];
            $rules['bundle_items.*.subcategory_id'] = ['nullable', 'required_without_all:bundle_items.*.product_id,bundle_items.*.category_id', 'exists:subcategories,id'];
            $rules['bundle_items.*.required_quantity'] = ['required', 'integer', 'min:1'];
        }

        if ($type === 'minimum_spend') {
            $rules['minimum_spends'] = ['required', 'array', 'min:1'];
            $rules['minimum_spends.*.currency_id'] = ['required', 'exists:currencies,id'];
            $rules['minimum_spends.*.minimum_amount'] = ['required', 'numeric', 'min:0.01'];
        }

        if ($type === 'category') {
            $rules['category_id'] = ['required', 'exists:categories,id'];
        }

        if ($type === 'brand') {
            $rules['brand_id'] = ['required', 'exists:brands,id'];
        }

        // Fixed discount requires amounts
        if ($discountType === 'fixed') {
            $rules['amounts'] = ['required', 'array', 'min:1'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'product_ids.required' => 'At least one product must be selected.',
            'bundle_items.required' => 'At least one bundle entry is required.',
            'bundle_items.min' => 'A bundle requires at least one entry.',
            'minimum_spends.required' => 'At least one minimum spend threshold is required.',
            'amounts.required' => 'At least one currency amount is required for fixed discounts.',
            'category_id.required' => 'A category must be selected.',
            'brand_id.required' => 'A brand must be selected.',
        ];
    }
}

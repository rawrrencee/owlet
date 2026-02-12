<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'show_company_logo' => ['boolean'],
            'show_company_address' => ['boolean'],
            'show_company_uen' => ['boolean'],
            'tax_mode' => ['required', 'in:none,store,manual'],
            'tax_store_id' => ['nullable', 'required_if:tax_mode,store', 'exists:stores,id'],
            'tax_percentage' => ['nullable', 'required_if:tax_mode,manual', 'numeric', 'min:0', 'max:100'],
            'tax_inclusive' => ['boolean'],
            'terms_and_conditions' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'external_notes' => ['nullable', 'string'],
            'payment_terms' => ['nullable', 'string'],
            'validity_date' => ['nullable', 'date'],
            'customer_discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.currency_id' => ['required', 'exists:currencies,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.sort_order' => ['integer', 'min:0'],
            'items.*.offer_id' => ['nullable', 'exists:offers,id'],
            'items.*.offer_name' => ['nullable', 'string'],
            'items.*.offer_discount_type' => ['nullable', 'string'],
            'items.*.offer_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.offer_is_combinable' => ['nullable', 'boolean'],
            'items.*.customer_discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_mode_ids' => ['nullable', 'array'],
            'payment_mode_ids.*' => ['exists:payment_modes,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one line item is required.',
            'items.min' => 'At least one line item is required.',
            'items.*.product_id.required' => 'Each line item must have a product.',
            'items.*.currency_id.required' => 'Each line item must have a currency.',
            'items.*.quantity.required' => 'Each line item must have a quantity.',
            'items.*.unit_price.required' => 'Each line item must have a unit price.',
        ];
    }
}

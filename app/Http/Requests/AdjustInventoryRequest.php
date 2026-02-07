<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustInventoryRequest extends FormRequest
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
            'product_id' => ['required', 'exists:products,id'],
            'store_id' => ['required', 'exists:stores,id'],
            'adjust_quantity' => ['required', 'integer', 'not_in:0'],
            'stocktake_id' => ['nullable', 'exists:stocktakes,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'adjust_quantity.not_in' => 'The adjustment quantity cannot be zero.',
        ];
    }
}

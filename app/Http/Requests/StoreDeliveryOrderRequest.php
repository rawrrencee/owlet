<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryOrderRequest extends FormRequest
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
            'store_id_from' => ['required', 'exists:stores,id'],
            'store_id_to' => ['required', 'exists:stores,id', 'different:store_id_from'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_id_to.different' => 'The destination store must be different from the source store.',
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
        ];
    }
}

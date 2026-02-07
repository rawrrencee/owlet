<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddStocktakeItemRequest extends FormRequest
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
            'counted_quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStoreCurrencyRequest extends FormRequest
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
        $storeId = $this->route('store')?->id;

        return [
            'currency_id' => [
                'required',
                'exists:currencies,id',
                Rule::unique('store_currencies', 'currency_id')
                    ->where('store_id', $storeId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'currency_id.unique' => 'This currency is already assigned to this store.',
        ];
    }
}

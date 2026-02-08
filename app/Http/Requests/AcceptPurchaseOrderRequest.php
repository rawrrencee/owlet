<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcceptPurchaseOrderRequest extends FormRequest
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
            'store_id' => ['required', 'exists:stores,id'],
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.received_quantity' => ['required', 'integer', 'min:0'],
            'items.*.correction_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            foreach ($items as $index => $item) {
                $originalItem = \App\Models\PurchaseOrderItem::find($item['id'] ?? null);
                if ($originalItem && isset($item['received_quantity'])) {
                    if ((int) $item['received_quantity'] !== $originalItem->quantity && empty($item['correction_note'])) {
                        $validator->errors()->add(
                            "items.{$index}.correction_note",
                            'A correction note is required when the received quantity differs from the ordered quantity.'
                        );
                    }
                }
            }
        });
    }
}

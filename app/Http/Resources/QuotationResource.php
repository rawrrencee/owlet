<?php

namespace App\Http\Resources;

use App\Services\QuotationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quotation_number' => $this->quotation_number,
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', fn () => $this->company ? [
                'id' => $this->company->id,
                'company_name' => $this->company->company_name,
                'registration_number' => $this->company->registration_number,
                'tax_registration_number' => $this->company->tax_registration_number,
                'address_1' => $this->company->address_1,
                'address_2' => $this->company->address_2,
                'city' => $this->company->city,
                'state' => $this->company->state,
                'postal_code' => $this->company->postal_code,
                'email' => $this->company->email,
                'phone_number' => $this->company->phone_number,
                'logo' => $this->company->logo,
                'logo_url' => $this->company->logo ? route('companies.logo', $this->company->id) : null,
            ] : null),
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'first_name' => $this->customer->first_name,
                'last_name' => $this->customer->last_name,
                'full_name' => $this->customer->full_name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
                'discount_percentage' => $this->customer->discount_percentage,
            ] : null),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'show_company_logo' => $this->show_company_logo,
            'show_company_address' => $this->show_company_address,
            'show_company_uen' => $this->show_company_uen,
            'tax_mode' => $this->tax_mode,
            'tax_store_id' => $this->tax_store_id,
            'tax_store' => $this->whenLoaded('taxStore', fn () => $this->taxStore ? [
                'id' => $this->taxStore->id,
                'store_name' => $this->taxStore->store_name,
                'store_code' => $this->taxStore->store_code,
                'tax_percentage' => $this->taxStore->tax_percentage ?? null,
            ] : null),
            'tax_percentage' => $this->tax_percentage,
            'tax_inclusive' => $this->tax_inclusive,
            'terms_and_conditions' => $this->terms_and_conditions,
            'internal_notes' => $this->internal_notes,
            'external_notes' => $this->external_notes,
            'payment_terms' => $this->payment_terms,
            'validity_date' => $this->validity_date?->toDateString(),
            'customer_discount_percentage' => $this->customer_discount_percentage,
            'items' => $this->whenLoaded('items', fn () => QuotationItemResource::collection($this->items)->resolve()),
            'payment_modes' => $this->whenLoaded('paymentModes', fn () => $this->paymentModes->map(fn ($pm) => [
                'id' => $pm->id,
                'name' => $pm->name,
                'code' => $pm->code,
            ])),
            'item_count' => $this->whenCounted('items', $this->items_count),
            'totals' => $this->when(
                $this->relationLoaded('items'),
                fn () => app(QuotationService::class)->computeTotals($this->resource)
            ),
            'share_token' => $this->share_token,
            'share_url' => $this->share_token ? url("/q/{$this->share_token}") : null,
            'has_password' => (bool) $this->share_password_hash,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'viewed_at' => $this->viewed_at?->toIso8601String(),
            'signed_at' => $this->signed_at?->toIso8601String(),
            'expired_at' => $this->expired_at?->toIso8601String(),
            'created_by_user' => $this->whenLoaded('createdBy', fn () => $this->createdBy ? [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ] : null),
            'updated_by_user' => $this->whenLoaded('updatedBy', fn () => $this->updatedBy ? [
                'id' => $this->updatedBy->id,
                'name' => $this->updatedBy->name,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}

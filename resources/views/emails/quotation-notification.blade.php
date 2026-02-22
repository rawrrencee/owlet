@component('mail::message')
# Quotation {{ $action }}

**Quotation #:** {{ $quotation->quotation_number }}

**Status:** {{ $quotation->status->value }}

@if($company)
**Company:** {{ $company->company_name }}
@endif

@if($customer)
**Customer:** {{ $customer->full_name }}
@endif

@if($quotation->validity_date)
**Valid Until:** {{ \Carbon\Carbon::parse($quotation->validity_date)->format('d M Y') }}
@endif

@if($quotation->sent_at)
**Sent At:** {{ $quotation->sent_at->format('d M Y, H:i') }}
@endif

## Items

@component('mail::table')
| Product | Qty | Unit Price | Discount | Line Total |
|:--------|:----|:-----------|:---------|:-----------|
@foreach($items as $item)
| {{ $item->product->product_name ?? 'N/A' }}{{ $item->product->variant_name ? ' - ' . $item->product->variant_name : '' }} | {{ $item->quantity }} | {{ number_format($item->unit_price, 2) }} | {{ number_format($item->line_discount, 2) }} | {{ number_format($item->line_total, 2) }} |
@endforeach
@endcomponent

**Total Items:** {{ $items->count() }}

@if($quotation->payment_terms)
**Payment Terms:** {{ $quotation->payment_terms }}
@endif

@if($quotation->internal_notes)
**Notes:** {{ $quotation->internal_notes }}
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent

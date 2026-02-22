@component('mail::message')
# Refund Processed

A refund has been processed on the following transaction:

**Transaction #:** {{ $transaction->transaction_number }}

**Store:** {{ $store->store_name }} ({{ $store->store_code }})

**Employee:** {{ $employee->full_name }}

@if($customer)
**Customer:** {{ $customer->full_name }}
@endif

**Currency:** {{ $currency->code }}

## Refunded Items

{{ $refundSummary }}

## Current Items

@component('mail::table')
| Product | Qty | Unit Price | Line Total | Refunded |
|:--------|:----|:-----------|:-----------|:---------|
@foreach($items as $item)
| {{ $item->product_name }}{{ $item->variant_name ? ' - ' . $item->variant_name : '' }} | {{ $item->quantity }} | {{ number_format($item->unit_price, 2) }} | {{ number_format($item->line_total, 2) }} | {{ $item->is_refunded ? 'Yes' : 'No' }} |
@endforeach
@endcomponent

## Updated Totals

**Total:** {{ number_format($transaction->total, 2) }}
**Refund Amount:** {{ number_format($transaction->refund_amount, 2) }}
**Amount Paid:** {{ number_format($transaction->amount_paid, 2) }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent

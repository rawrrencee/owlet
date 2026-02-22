@component('mail::message')
# Transaction Completed

A transaction has been completed with the following details:

**Transaction #:** {{ $transaction->transaction_number }}

**Store:** {{ $store->store_name }} ({{ $store->store_code }})

**Employee:** {{ $employee->full_name }}

**Date:** {{ $transaction->checkout_date->format('d M Y, H:i') }}

@if($customer)
**Customer:** {{ $customer->full_name }}
@endif

**Currency:** {{ $currency->code }}

## Items

@component('mail::table')
| Product | Qty | Unit Price | Discount | Line Total |
|:--------|:----|:-----------|:---------|:-----------|
@foreach($items as $item)
| {{ $item->product_name }}{{ $item->variant_name ? ' - ' . $item->variant_name : '' }} | {{ $item->quantity }} | {{ number_format($item->unit_price, 2) }} | {{ number_format($item->line_subtotal - $item->line_total, 2) }} | {{ number_format($item->line_total, 2) }} |
@endforeach
@endcomponent

## Payments

@component('mail::table')
| Payment Method | Amount |
|:---------------|:-------|
@foreach($payments as $payment)
| {{ $payment->payment_mode_name }} | {{ number_format($payment->amount, 2) }} |
@endforeach
@endcomponent

## Summary

@component('mail::table')
| | |
|:---|---:|
| Subtotal | {{ number_format($transaction->subtotal, 2) }} |
@if($transaction->offer_discount > 0)
| Offer Discount | -{{ number_format($transaction->offer_discount, 2) }} |
@endif
@if($transaction->bundle_discount > 0)
| Bundle Discount | -{{ number_format($transaction->bundle_discount, 2) }} |
@endif
@if($transaction->minimum_spend_discount > 0)
| Min. Spend Discount | -{{ number_format($transaction->minimum_spend_discount, 2) }} |
@endif
@if($transaction->customer_discount > 0)
| Customer Discount | -{{ number_format($transaction->customer_discount, 2) }} |
@endif
@if($transaction->manual_discount > 0)
| Manual Discount | -{{ number_format($transaction->manual_discount, 2) }} |
@endif
@if($transaction->tax_amount > 0)
| Tax ({{ $transaction->tax_percentage }}%) | {{ number_format($transaction->tax_amount, 2) }} |
@endif
| **Total** | **{{ number_format($transaction->total, 2) }}** |
| Amount Paid | {{ number_format($transaction->amount_paid, 2) }} |
@if($transaction->change_amount > 0)
| Change | {{ number_format($transaction->change_amount, 2) }} |
@endif
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent

@component('mail::message')
# Transaction Amended

A completed transaction has been modified:

**Transaction #:** {{ $transaction->transaction_number }}

**Store:** {{ $store->store_name }} ({{ $store->store_code }})

**Employee:** {{ $employee->full_name }}

@if($customer)
**Customer:** {{ $customer->full_name }}
@endif

**Currency:** {{ $currency->code }}

## Change Details

@if($changeDetails)
@if($changeDetails['type'] === 'item_added')
**Items Added:**
@foreach($changeDetails['items'] as $item)
- {{ $item['product_name'] }}{{ !empty($item['variant_name']) ? ' - ' . $item['variant_name'] : '' }} x{{ $item['quantity'] }}
@endforeach

@elseif($changeDetails['type'] === 'item_removed')
**Items Removed:**
@foreach($changeDetails['items'] as $item)
- {{ $item['product_name'] }}{{ !empty($item['variant_name']) ? ' - ' . $item['variant_name'] : '' }} x{{ $item['quantity'] }}
@endforeach

@elseif($changeDetails['type'] === 'item_modified')
**Item Modified:** {{ $changeDetails['product_name'] }}
@foreach($changeDetails['changes'] as $change)
- {{ $change }}
@endforeach

@elseif($changeDetails['type'] === 'payment_added')
**Payment Added:** {{ $changeDetails['method'] }} — {{ number_format((float)$changeDetails['amount'], 2) }}
@endif
@else
{{ $changeSummary }}
@endif

## Current Items

@component('mail::table')
| Product | Qty | Unit Price | Line Total | Refunded |
|:--------|:----|:-----------|:-----------|:---------|
@foreach($items as $item)
| {{ $item->product_name }}{{ $item->variant_name ? ' - ' . $item->variant_name : '' }} | {{ $item->quantity }} | {{ number_format($item->unit_price, 2) }} | {{ number_format($item->line_total, 2) }} | {{ $item->is_refunded ? 'Yes' : 'No' }} |
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

## Updated Totals

@component('mail::table')
| | |
|:---|---:|
| Subtotal | {{ number_format($transaction->subtotal, 2) }} |
| Total Discounts | -{{ number_format($transaction->offer_discount + $transaction->bundle_discount + $transaction->minimum_spend_discount + $transaction->customer_discount + $transaction->manual_discount, 2) }} |
@if($transaction->tax_amount > 0)
| Tax ({{ $transaction->tax_percentage }}%) | {{ number_format($transaction->tax_amount, 2) }} |
@endif
| **Total** | **{{ number_format($transaction->total, 2) }}** |
| Amount Paid | {{ number_format($transaction->amount_paid, 2) }} |
@if($transaction->balance_due > 0)
| **Balance Due** | **{{ number_format($transaction->balance_due, 2) }}** |
@endif
@endcomponent

@if(isset($versionHistory) && $versionHistory->count() > 0)
## Change History

@component('mail::table')
| Version | Change | By | Date |
|:--------|:-------|:---|:-----|
@foreach($versionHistory as $index => $version)
@if($showVersionEllipsis && $index === 3)
| | ··· | | |
@endif
| v{{ $version->version_number }} | {{ $version->change_summary }} | {{ $version->changedByUser?->name ?? 'System' }} | {{ $version->created_at->format('d M Y, H:i') }} |
@endforeach
@endcomponent
@endif

@component('mail::button', ['url' => url("/transactions/{$transaction->id}")])
View Transaction
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent

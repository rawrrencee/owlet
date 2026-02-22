@component('mail::message')
# Delivery Order {{ $action }}

**Order #:** {{ $order->order_number }}

**Status:** {{ $order->status->value }}

**From:** {{ $storeFrom->store_name }} ({{ $storeFrom->store_code }})

**To:** {{ $storeTo->store_name }} ({{ $storeTo->store_code }})

@if($order->submittedByUser)
**Submitted By:** {{ $order->submittedByUser->name }}
@endif

@if($order->submitted_at)
**Submitted At:** {{ $order->submitted_at->format('d M Y, H:i') }}
@endif

@if($order->resolvedByUser)
**Resolved By:** {{ $order->resolvedByUser->name }}
@endif

@if($order->resolved_at)
**Resolved At:** {{ $order->resolved_at->format('d M Y, H:i') }}
@endif

@if($order->rejection_reason)
**Rejection Reason:** {{ $order->rejection_reason }}
@endif

## Items

@component('mail::table')
| Product | Ordered Qty | Received Qty | Note |
|:--------|:------------|:-------------|:-----|
@foreach($items as $item)
| {{ $item->product->product_name ?? 'N/A' }}{{ $item->product->variant_name ? ' - ' . $item->product->variant_name : '' }} | {{ $item->quantity }} | {{ $item->received_quantity ?? '-' }} | {{ $item->correction_note ?? '-' }} |
@endforeach
@endcomponent

**Total Items:** {{ $items->count() }}

@if($order->notes)
**Notes:** {{ $order->notes }}
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent

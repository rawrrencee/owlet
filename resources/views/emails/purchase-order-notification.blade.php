@component('mail::message')
# Purchase Order {{ $action }}

**Order #:** {{ $order->order_number }}

**Status:** {{ $order->status->value }}

**Supplier:** {{ $supplier->supplier_name ?? 'Unknown' }}

@if($store)
**Store:** {{ $store->store_name }} ({{ $store->store_code }})
@endif

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
| Product | Qty | Unit Cost | Total | Received |
|:--------|:----|:----------|:------|:---------|
@foreach($items as $item)
| {{ $item->product->product_name ?? 'N/A' }}{{ $item->product->variant_name ? ' - ' . $item->product->variant_name : '' }} | {{ $item->quantity }} | {{ number_format($item->unit_cost, 2) }} | {{ number_format($item->totalCost, 2) }} | {{ $item->received_quantity ?? '-' }} |
@endforeach
@endcomponent

**Total Items:** {{ $items->count() }}

@if($order->notes)
**Notes:** {{ $order->notes }}
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent

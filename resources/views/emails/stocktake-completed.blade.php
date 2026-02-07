@component('mail::message')
# Stocktake Completed

A stocktake has been submitted with the following details:

**Store:** {{ $store->store_name }} ({{ $store->store_code }})

**Employee:** {{ $employee->full_name }}

**Submitted At:** {{ $stocktake->submitted_at->format('d M Y, H:i') }}

**Has Issues:** {{ $stocktake->has_issues ? 'Yes' : 'No' }}

## Items Counted

@component('mail::table')
| Product | Product # | Counted Qty | Status |
|:--------|:----------|:------------|:-------|
@foreach($items as $item)
| {{ $item->product->product_name }}{{ $item->product->variant_name ? ' - ' . $item->product->variant_name : '' }} | {{ $item->product->product_number }} | {{ $item->counted_quantity }} | {{ $item->has_discrepancy ? 'Discrepancy' : 'OK' }} |
@endforeach
@endcomponent

**Total Items:** {{ $items->count() }}
**Items with Discrepancies:** {{ $items->where('has_discrepancy', true)->count() }}

@if($stocktake->notes)
**Notes:** {{ $stocktake->notes }}
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent

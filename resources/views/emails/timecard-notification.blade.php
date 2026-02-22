@component('mail::message')
# Timecard {{ $action }}

**Employee:** {{ $employee->full_name }}

**Store:** {{ $store->store_name }} ({{ $store->store_code }})

**Status:** {{ $timecard->status_label }}

**Date:** {{ $timecard->start_date->format('d M Y') }}

@if($timecard->start_date)
**Clock In:** {{ $timecard->start_date->format('H:i') }}
@endif

@if($timecard->end_date)
**Clock Out:** {{ $timecard->end_date->format('H:i') }}
@endif

@if($timecard->hours_worked)
**Hours Worked:** {{ number_format($timecard->hours_worked, 2) }}
@endif

@if($timecard->is_incomplete)
**Incomplete:** Yes
@endif

@if($timecard->is_inaccurate)
**Inaccurate (User-Provided End Time):** Yes
@endif

@if($details->count() > 0)
## Time Entries

@component('mail::table')
| Type | Start | End | Hours |
|:-----|:------|:----|:------|
@foreach($details as $detail)
| {{ $detail->typeLabel }} | {{ $detail->start_date->format('H:i') }} | {{ $detail->end_date ? $detail->end_date->format('H:i') : 'In Progress' }} | {{ $detail->hours ? number_format($detail->hours, 2) : '-' }} |
@endforeach
@endcomponent
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent

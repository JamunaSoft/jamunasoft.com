<x-mail::message>
# Thank you, {{ $lead->name }}!

We have received your quotation request and our team will review it shortly.

**Your reference number:** `{{ $lead->reference }}`

Please keep this reference for future communication.

@if ($lead->service)
**Service:** {{ $lead->service->name }}
@endif
@if ($lead->budget)
**Estimated budget:** {{ $lead->budget }}
@endif
@if ($lead->timeline)
**Desired timeline:** {{ $lead->timeline }}
@endif

One of our consultants will contact you within one business day to discuss your project in detail.

<x-mail::button :url="config('app.url')">
Visit Our Website
</x-mail::button>

Thanks,<br>
{{ settings('company_name', config('app.name')) }}
</x-mail::message>

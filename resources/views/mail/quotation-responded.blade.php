<x-mail::message>
# Quotation {{ $quotation->status->value === 'accepted' ? 'accepted 🎉' : 'declined' }}

**{{ $quotation->reference }}** — ৳{{ number_format((float) $quotation->total, 2) }}

| | |
|---|---|
| Customer | {{ $quotation->customer_name }} |
| Email | {{ $quotation->customer_email }} |
| Responded | {{ $quotation->responded_at?->format('d M Y H:i') }} |

@if ($quotation->status->value === 'accepted')
Next step: convert it to an invoice from the admin panel.
@endif

<x-mail::button :url="url('/admin/quotations')">
Open Quotations
</x-mail::button>

{{ settings('company_name', config('app.name')) }}
</x-mail::message>

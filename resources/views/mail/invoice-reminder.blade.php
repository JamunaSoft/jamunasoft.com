<x-mail::message>
# {{ $invoice->isOverdue() ? 'Invoice overdue' : 'Payment reminder' }}

Dear {{ $invoice->user->name }},

@if ($invoice->isOverdue())
Invoice **{{ $invoice->reference }}** was due on **{{ $invoice->due_at?->format('d M Y') }}** and is still unpaid. To avoid any interruption of your services, please pay it as soon as possible.
@else
This is a friendly reminder that invoice **{{ $invoice->reference }}** is due on **{{ $invoice->due_at?->format('d M Y') }}**.
@endif

**Amount due:** ৳{{ number_format($invoice->balance(), 2) }}
@if (($previousDue = $invoice->previousDueAmount()) > 0)
<br>**Previous due (earlier invoices):** ৳{{ number_format($previousDue, 2) }}<br>
**Total payable:** ৳{{ number_format($invoice->balance() + $previousDue, 2) }}
@endif

@foreach ($invoice->items as $item)
- {{ $item->displayTitle() }}
@endforeach

@if (settings('domain_payment_instructions'))
## How to pay

{{ settings('domain_payment_instructions') }}

Please mention the invoice number **{{ $invoice->reference }}** with the payment.
@endif

Already paid? Then please ignore this email — it can take us a little while to confirm a payment.

<x-mail::button :url="$invoice->publicUrl()">
View &amp; Pay Invoice
</x-mail::button>

Thanks,<br>
{{ settings('company_name', config('app.name')) }}
</x-mail::message>

<x-mail::message>
# Your invoices from {{ settings('company_name', config('app.name')) }}

Dear {{ $invoices[0]->user->name }},

{{ count($invoices) }} invoices have been generated for your account. Each one is attached as a PDF, and every invoice can be viewed and paid online separately.

| Billed to | Invoice | Due date | Amount due |
|---|---|---|---:|
@foreach ($invoices as $invoice)
| {{ $invoice->billedTo()['company'] ?: $invoice->user->name }} | {{ $invoice->reference }} | {{ $invoice->due_at?->format('d M Y') ?? '—' }} | ৳{{ number_format($invoice->balance(), 2) }} |
@endforeach
| | | **Total payable** | **৳{{ number_format(collect($invoices)->sum(fn ($invoice) => $invoice->balance()), 2) }}** |

@foreach ($invoices as $invoice)
<x-mail::button :url="$invoice->publicUrl()">
View &amp; Pay {{ $invoice->reference }}
</x-mail::button>
@endforeach

@if (settings('domain_payment_instructions'))
## How to pay

{{ settings('domain_payment_instructions') }}

Please mention the invoice numbers with your payment.
@endif

Regards,<br>
{{ settings('company_name', config('app.name')) }}
</x-mail::message>

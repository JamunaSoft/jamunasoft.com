<x-mail::message>
# Invoice {{ $invoice->reference }}

Dear {{ $invoice->user->name }}@if (filled($invoice->billedTo()['company'])) ({{ $invoice->billedTo()['company'] }})@endif,

This is a notice that an invoice has been generated on {{ $invoice->created_at->format('l, F jS, Y') }}.

**Invoice {{ $invoice->reference }}**<br>
**Amount Due:** ৳{{ number_format($invoice->balance(), 2) }}<br>
**Due Date:** {{ $invoice->due_at?->format('l, F jS, Y') ?? '—' }}
@if (($previousDue = $invoice->previousDueAmount()) > 0)
<br>**Previous Due (earlier invoices):** ৳{{ number_format($previousDue, 2) }}<br>
**Total Payable:** ৳{{ number_format($invoice->balance() + $previousDue, 2) }}
@endif

## Invoice Items

| Item | Qty | Amount |
|---|---|---|
@foreach ($invoice->items as $item)
| {{ $item->displayTitle() }}@if ($item->displayDescription())<br><small>{{ str_replace(["\r\n", "\r", "\n"], ' · ', $item->displayDescription()) }}</small>@endif | {{ (float) $item->quantity == 1 ? '1' : rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }} | ৳{{ number_format((float) $item->total, 2) }} |
@endforeach
@if ((float) $invoice->discount > 0)
| **Sub Total** | | ৳{{ number_format((float) $invoice->subtotal, 2) }} |
| **Discount** | | −৳{{ number_format((float) $invoice->discount, 2) }} |
@endif
| **Total** | | **৳{{ number_format((float) $invoice->total, 2) }}** |

@if (settings('domain_payment_instructions'))
## How to pay

{{ settings('domain_payment_instructions') }}

Please mention the invoice number **{{ $invoice->reference }}** with the payment.
@endif

A PDF copy of this invoice is attached. You can view and pay the invoice online any time using the button below.

<x-mail::button :url="$invoice->publicUrl()">
View &amp; Pay Invoice
</x-mail::button>

Regards,<br>
{{ settings('company_name', config('app.name')) }}
</x-mail::message>

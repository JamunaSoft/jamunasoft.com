<x-mail::message>
# Invoice {{ $invoice->reference }}

Dear {{ $invoice->user->name }}, a new invoice has been issued to your account.

| Item | Qty | Amount |
|---|---|---|
@foreach ($invoice->items as $item)
| {{ $item->description }} | {{ (float) $item->quantity == 1 ? '1' : rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }} | ৳{{ number_format((float) $item->total, 2) }} |
@endforeach
@if ((float) $invoice->discount > 0)
| **Discount** | | −৳{{ number_format((float) $invoice->discount, 2) }} |
@endif
| **Total** | | **৳{{ number_format((float) $invoice->total, 2) }}** |

**Due date:** {{ $invoice->due_at?->format('d M Y') ?? '—' }}

@if (settings('domain_payment_instructions'))
## How to pay

{{ settings('domain_payment_instructions') }}

Please mention the invoice number **{{ $invoice->reference }}** with the payment.
@endif

You can view your invoices any time in our client area.

<x-mail::button :url="url('/client/invoices')">
View Invoice
</x-mail::button>

Thanks,<br>
{{ settings('company_name', config('app.name')) }}
</x-mail::message>

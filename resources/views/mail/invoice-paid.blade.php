<x-mail::message>
# Payment received — thank you!

Dear {{ $invoice->user->name }}, we have received your payment for invoice **{{ $invoice->reference }}**.

| | |
|---|---|
| Invoice | {{ $invoice->reference }} |
| Amount paid | ৳{{ number_format((float) $invoice->amount_paid, 2) }} |
| Date | {{ $invoice->paid_at?->format('d M Y') }} |

@foreach ($invoice->items as $item)
- {{ $item->description }}
@endforeach

Everything on this invoice is now being taken care of automatically.

<x-mail::button :url="url('/client/invoices')">
View Receipt
</x-mail::button>

Thanks,<br>
{{ settings('company_name', config('app.name')) }}
</x-mail::message>

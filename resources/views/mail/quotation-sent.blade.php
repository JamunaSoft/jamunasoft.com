<x-mail::message>
# Your quotation is ready

Dear {{ $quotation->customer_name }},

Thank you for your interest. Please find your quotation **{{ $quotation->reference }}** below.

| Item | Qty | Amount |
|---|---|---|
@foreach ($quotation->items as $item)
| {{ $item->description }} | {{ (float) $item->quantity == 1 ? '1' : rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }} | ৳{{ number_format((float) $item->total, 2) }} |
@endforeach
@if ((float) $quotation->discount > 0)
| **Discount** | | −৳{{ number_format((float) $quotation->discount, 2) }} |
@endif
| **Total** | | **৳{{ number_format((float) $quotation->total, 2) }}** |

@if ($quotation->valid_until)
This quotation is valid until **{{ $quotation->valid_until->format('d M Y') }}**.
@endif

You can review the full quotation and accept it online:

<x-mail::button :url="$quotation->publicUrl()">
View & Accept Quotation
</x-mail::button>

Questions? Just reply to this email.

Thanks,<br>
{{ settings('company_name', config('app.name')) }}
</x-mail::message>

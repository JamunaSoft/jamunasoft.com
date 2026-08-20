<x-mail::message>
# New domain order

**{{ $order->reference }}** — {{ $order->type->getLabel() }} of **{{ $order->domain_name }}**

| | |
|---|---|
| Customer | {{ $order->customer_name }} |
| Email | {{ $order->customer_email }} |
| Phone | {{ $order->customer_phone ?? '—' }} |
| Period | {{ $order->years }} {{ str('year')->plural($order->years) }} |
| Amount | ৳{{ number_format((float) $order->amount, 2) }} {{ $order->currency }} |
| Status | {{ $order->status->getLabel() }} |

Confirm the payment in the admin panel to start registration automatically.

<x-mail::button :url="url('/admin/domain-orders')">
Open Domain Orders
</x-mail::button>

{{ settings('company_name', config('app.name')) }}
</x-mail::message>

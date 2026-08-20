<x-mail::message>
# Thank you, {{ $order->customer_name }}!

We have received your order for **{{ $order->domain_name }}**.

**Order reference:** `{{ $order->reference }}`

| | |
|---|---|
| Domain | {{ $order->domain_name }} |
| Type | {{ $order->type->getLabel() }} |
| Period | {{ $order->years }} {{ str('year')->plural($order->years) }} |
| Amount | ৳{{ number_format((float) $order->amount, 2) }} {{ $order->currency }} |

@if (settings('domain_payment_instructions'))
## How to pay

{{ settings('domain_payment_instructions') }}

Please mention your order reference **{{ $order->reference }}** with the payment.
@endif

Your domain will be activated as soon as we confirm your payment, and we will email you again when it is ready.

<x-mail::button :url="route('domains.order.status', $order->reference)">
View Order Status
</x-mail::button>

Thanks,<br>
{{ settings('company_name', config('app.name')) }}
</x-mail::message>

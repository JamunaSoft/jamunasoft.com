<x-mail::message>
# Transfer started

Hello {{ $order->customer_name }},

The transfer request for **{{ $order->domain_name }}** has been accepted and is being processed. The transfer is not complete yet.

**Order reference:** `{{ $order->reference }}`

Please check the domain owner's inbox and spam folder for a transfer approval email from the current registrar. Approving it may speed up the transfer. Transfers commonly take around five days.

We will email you when it is complete.

Thanks,<br>
{{ settings('company_name', config('app.name')) }}
</x-mail::message>

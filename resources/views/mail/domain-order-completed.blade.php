<x-mail::message>
# Good news, {{ $order->customer_name }}!

Your domain **{{ $order->domain_name }}** is now active.

**Order reference:** `{{ $order->reference }}`

@if ($order->type->value === 'register')
The domain has been registered for {{ $order->years }} {{ str('year')->plural($order->years) }}. If you need DNS changes or want the domain pointed at your website or email, just reply to this email and our team will take care of it.
@else
The renewal for {{ $order->years }} {{ str('year')->plural($order->years) }} has been applied.
@endif

You can manage your domain — DNS records, nameservers and renewals — in our client area. Signing in for the first time? Use "Forgot password" with this email address to set your password.

<x-mail::button :url="url('/client')">
Open Client Area
</x-mail::button>

Thanks,<br>
{{ settings('company_name', config('app.name')) }}
</x-mail::message>

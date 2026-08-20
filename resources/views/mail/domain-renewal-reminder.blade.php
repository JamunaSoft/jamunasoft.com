<x-mail::message>
# Your domain expires soon

**{{ $domain->name }}** expires on **{{ $domain->expires_at?->format('d M Y') }}** — {{ $daysLeft }} {{ str('day')->plural($daysLeft) }} from now.

If the domain is not renewed in time, your website and email on it will stop working, and after expiry the domain can be registered by someone else.

@if ($domain->user_id)
Renewing takes a minute in our client area: open **My Domains**, press **Renew** next to {{ $domain->name }}, and follow the payment instructions.

<x-mail::button :url="url('/client')">
Renew in Client Area
</x-mail::button>
@else
Reply to this email or contact us and we will renew it for you.
@endif

Thanks,<br>
{{ settings('company_name', config('app.name')) }}
</x-mail::message>

<x-mail::message>
# Confirm your subscription

Thanks for subscribing to the {{ settings('company_name', config('app.name')) }} newsletter.

Please confirm your email address by clicking the button below:

<x-mail::button :url="route('newsletter.confirm', $subscriber->token)">
Confirm Subscription
</x-mail::button>

If you did not request this, you can safely ignore this email.

Thanks,<br>
{{ settings('company_name', config('app.name')) }}
</x-mail::message>

<x-mail::message>
# Welcome to {{ settings('company_name', config('app.name')) }}

Dear {{ $user->name }},

A client account has been created for you on our client portal. From there you can view and pay invoices, manage your hosting and domains, and open support tickets.

**Your login email:** {{ $user->email }}

Set your password using the button below to activate your account.

<x-mail::button :url="$setPasswordUrl">
Set My Password
</x-mail::button>

This link expires after a short time — if it has expired, simply use **"Forgot password?"** on the login page with your email address to get a new one.

Regards,<br>
{{ settings('company_name', config('app.name')) }}
</x-mail::message>

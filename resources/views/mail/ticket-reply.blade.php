<x-mail::message>
# We replied to your ticket

Dear {{ $ticket->user->name }}, our team has replied to your support ticket **{{ $ticket->reference }}** — {{ $ticket->subject }}:

> {{ $reply->message }}

You can reply from the client area — please do not reply directly to this email.

<x-mail::button :url="url('/client/tickets')">
View Ticket
</x-mail::button>

Thanks,<br>
{{ settings('company_name', config('app.name')) }}
</x-mail::message>

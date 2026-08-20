<x-mail::message>
# {{ $ticket->status->value === 'customer_reply' ? 'Customer replied to a ticket' : 'New support ticket' }}

**{{ $ticket->reference }}** — {{ $ticket->subject }}

| | |
|---|---|
| Client | {{ $ticket->user->name }} ({{ $ticket->user->email }}) |
| Priority | {{ $ticket->priority->getLabel() }} |
| Status | {{ $ticket->status->getLabel() }} |

**Latest message:**

> {{ str($ticket->messages()->latest('created_at')->first()?->message ?? '')->limit(500) }}

<x-mail::button :url="url('/admin/tickets')">
Open Tickets
</x-mail::button>

{{ settings('company_name', config('app.name')) }}
</x-mail::message>

<x-mail::message>
# Thank you, {{ $contactMessage->name }}!

We have received your message and will get back to you as soon as possible — usually within one business day.

@if ($contactMessage->subject)
**Subject:** {{ $contactMessage->subject }}
@endif

**Your message:**

{{ $contactMessage->message }}

Thanks,<br>
{{ settings('company_name', config('app.name')) }}
</x-mail::message>

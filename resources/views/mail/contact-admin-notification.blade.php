<x-mail::message>
# New Contact Message

<x-mail::table>
| Field | Value |
|:------|:------|
| Name | {{ $contactMessage->name }} |
| Email | {{ $contactMessage->email }} |
| Phone | {{ $contactMessage->phone ?: '—' }} |
| Company | {{ $contactMessage->company ?: '—' }} |
| Service | {{ $contactMessage->service?->name ?: '—' }} |
| Subject | {{ $contactMessage->subject ?: '—' }} |
</x-mail::table>

**Message:**

{{ $contactMessage->message }}

<x-mail::button :url="url('/admin/contact-messages')">
Open in Admin Panel
</x-mail::button>

{{ settings('company_name', config('app.name')) }}
</x-mail::message>

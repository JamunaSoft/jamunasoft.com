<x-mail::message>
# New Lead Received

**Reference:** `{{ $lead->reference }}`

<x-mail::table>
| Field | Value |
|:------|:------|
| Name | {{ $lead->name }} |
| Company | {{ $lead->company ?: '—' }} |
| Phone | {{ $lead->phone ?: '—' }} |
| Email | {{ $lead->email ?: '—' }} |
| Service | {{ $lead->service?->name ?: '—' }} |
| Project type | {{ $lead->project_type ?: '—' }} |
| Budget | {{ $lead->budget ?: '—' }} |
| Timeline | {{ $lead->timeline ?: '—' }} |
| Source | {{ str_replace('_', ' ', $lead->source) }} |
</x-mail::table>

@if ($lead->message)
**Project description:**

{{ $lead->message }}
@endif

<x-mail::button :url="url('/admin/leads/' . $lead->id . '/edit')">
Open Lead in Admin Panel
</x-mail::button>

{{ settings('company_name', config('app.name')) }}
</x-mail::message>

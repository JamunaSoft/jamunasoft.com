<x-mail::message>
# Daily summary — {{ now()->format('l, d M Y') }}

@if (($data['pendingJobs'] > 5) || $data['failedJobs'] > 0)
> ⚠ **Queue needs attention:** {{ $data['pendingJobs'] }} pending {{ str('job')->plural($data['pendingJobs']) }}@if ($data['oldestJobAge']) (oldest: {{ $data['oldestJobAge'] }})@endif, {{ $data['failedJobs'] }} failed. Check the queue worker on the server.
@endif

## Last 24 hours

| | |
|---|---:|
| Invoices generated | {{ $data['invoicesCreated'] }} (৳{{ number_format($data['invoicesCreatedTotal'], 0) }}) |
| Payment reminders sent | {{ $data['remindersSent'] }} |
| **Payments received** | **{{ $data['paymentsReceived'] }} (৳{{ number_format($data['paymentsTotal'], 0) }})** |
| Expenses recorded | ৳{{ number_format($data['expensesRecorded'], 0) }} |
| New clients | {{ $data['newClients'] }} |
| New domain orders | {{ $data['newDomainOrders'] }} |
| New support tickets | {{ $data['newTickets'] }} |

## Needs attention

| | |
|---|---:|
| Unpaid invoices | {{ $data['unpaidCount'] }} (৳{{ number_format($data['unpaidTotal'], 0) }}) |
| Overdue invoices | {{ $data['overdueCount'] }} |
| Domains expiring ≤ 30 days | {{ $data['domainsExpiring'] }} |
| Services due ≤ 30 days | {{ $data['servicesDueSoon'] }} |
| Tickets awaiting reply | {{ $data['openTickets'] }} |

<x-mail::button :url="url('/admin')">
Open Admin Panel
</x-mail::button>

This email doubles as a heartbeat — if it stops arriving, the scheduler or mail queue on the server has stopped.

Regards,<br>
{{ settings('company_name', config('app.name')) }}
</x-mail::message>

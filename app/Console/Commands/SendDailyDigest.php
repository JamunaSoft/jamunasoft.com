<?php

namespace App\Console\Commands;

use App\Mail\DailyDigest;
use App\Models\ClientService;
use App\Models\Domain;
use App\Models\DomainOrder;
use App\Models\EmailLog;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendDailyDigest extends Command
{
    protected $signature = 'billing:daily-digest';

    protected $description = 'Email the daily automation & business summary to the admin (also acts as a cron/queue heartbeat)';

    public function handle(): int
    {
        $raw = trim((string) settings('digest_recipients', ''));

        if (strtolower($raw) === 'off') {
            $this->info('Daily digest is disabled (digest_recipients = off).');

            return self::SUCCESS;
        }

        $recipients = collect(explode(',', $raw))
            ->map(fn (string $email) => trim($email))
            ->filter()
            ->whenEmpty(fn ($collection) => collect([config('mail.billing_bcc')]))
            ->all();

        $since = now()->subDay();

        $pendingJobs = DB::table('jobs')->count();
        $oldestJob = DB::table('jobs')->min('created_at');

        $data = [
            // Last 24 hours of activity.
            'invoicesCreated' => Invoice::where('created_at', '>=', $since)->count(),
            'invoicesCreatedTotal' => (float) Invoice::where('created_at', '>=', $since)->sum('total'),
            'remindersSent' => EmailLog::where('type', 'invoice_reminder')->where('created_at', '>=', $since)->count(),
            'paymentsReceived' => Payment::where('paid_at', '>=', $since)->count(),
            'paymentsTotal' => (float) Payment::where('paid_at', '>=', $since)->sum('amount'),
            'expensesRecorded' => (float) Expense::where('created_at', '>=', $since)->sum('amount'),
            'newClients' => User::whereDoesntHave('roles')->where('created_at', '>=', $since)->count(),
            'newDomainOrders' => DomainOrder::where('created_at', '>=', $since)->count(),
            'newTickets' => Ticket::where('created_at', '>=', $since)->count(),

            // Current state needing attention.
            'unpaidCount' => Invoice::unpaid()->count(),
            'unpaidTotal' => (float) Invoice::unpaid()->sum(DB::raw('total - amount_paid')),
            'overdueCount' => Invoice::unpaid()->whereDate('due_at', '<', now())->count(),
            'overdueMuted' => Invoice::unpaid()->where('auto_remind', false)->whereDate('due_at', '<', now())->count(),
            'domainsExpiring' => Domain::expiringWithin(30)->count(),
            'servicesDueSoon' => ClientService::active()
                ->whereNotNull('next_due_at')
                ->whereBetween('next_due_at', [now(), now()->addDays(30)])
                ->count(),
            'openTickets' => Ticket::awaitingStaff()->count(),

            // Queue health.
            'pendingJobs' => $pendingJobs,
            'oldestJobAge' => $oldestJob ? now()->diffForHumans(Carbon::createFromTimestamp((int) $oldestJob), true) : null,
            'failedJobs' => DB::table('failed_jobs')->count(),
        ];

        Mail::to($recipients)->queue(new DailyDigest($data));

        $this->info('Daily digest queued for '.implode(', ', $recipients));

        return self::SUCCESS;
    }
}

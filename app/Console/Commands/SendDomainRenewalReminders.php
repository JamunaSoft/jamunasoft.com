<?php

namespace App\Console\Commands;

use App\Mail\DomainRenewalReminder;
use App\Models\Domain;
use App\Services\DomainOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDomainRenewalReminders extends Command
{
    /** Reminder thresholds in days before expiry, tightest first. */
    protected const THRESHOLDS = [3, 7, 15, 30];

    protected $signature = 'domains:send-renewal-reminders';

    protected $description = 'Email expiry reminders for domains approaching their expiration date';

    public function handle(DomainOrderService $orders): int
    {
        $sent = 0;

        Domain::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->each(function (Domain $domain) use ($orders, &$sent) {
                $daysLeft = (int) ceil(now()->diffInDays($domain->expires_at, false));

                $bucket = collect(self::THRESHOLDS)->first(fn (int $t) => $daysLeft <= $t);

                if ($bucket === null) {
                    // Outside the reminder window (e.g. after a renewal) — re-arm.
                    if ($domain->last_reminder_days !== null) {
                        $domain->update(['last_reminder_days' => null]);
                    }

                    return;
                }

                if ($domain->last_reminder_days !== null && $domain->last_reminder_days <= $bucket) {
                    return;
                }

                $recipients = $domain->user
                    ? $domain->user->billingEmails()
                    : $orders->notificationRecipients();

                foreach ($recipients as $recipient) {
                    Mail::to($recipient)->queue(new DomainRenewalReminder($domain, $daysLeft));
                }

                $domain->update(['last_reminder_days' => $bucket]);
                $sent++;

                $this->line("Reminder sent for {$domain->name} ({$daysLeft} days left).");
            });

        $this->info("Sent {$sent} renewal ".str('reminder')->plural($sent).'.');

        return self::SUCCESS;
    }
}

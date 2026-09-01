<?php

namespace App\Listeners;

use App\Models\EmailLog;
use Illuminate\Mail\Events\MessageSent;

/**
 * Flips matching "queued" email-log rows to "sent" once the mailer has
 * actually delivered the message to SMTP, so the Emails tab tells the truth.
 */
class MarkEmailLogSent
{
    public function handle(MessageSent $event): void
    {
        $recipients = array_map(
            fn ($address) => strtolower($address->getAddress()),
            $event->message->getTo() ?? [],
        );

        if ($recipients === []) {
            return;
        }

        EmailLog::query()
            ->where('status', 'queued')
            ->where('subject', $event->message->getSubject())
            ->where(function ($query) use ($recipients) {
                foreach ($recipients as $recipient) {
                    $query->orWhere('recipient', 'like', "%{$recipient}%");
                }
            })
            ->latest()
            ->take(1)
            ->get()
            ->each(fn (EmailLog $log) => $log->update(['status' => 'sent', 'sent_at' => now()]));
    }
}

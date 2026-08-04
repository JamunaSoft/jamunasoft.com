<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class FollowUpReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param Collection<int, Lead> $leads */
    public function __construct(public Collection $leads) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Overdue lead follow-ups ('.$this->leads->count().')')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('The following leads have overdue follow-ups:');

        foreach ($this->leads->take(15) as $lead) {
            $message->line("- **{$lead->name}** ({$lead->reference}) — due ".$lead->next_follow_up_at?->diffForHumans());
        }

        return $message->action('Open Leads', url('/admin/leads'));
    }
}

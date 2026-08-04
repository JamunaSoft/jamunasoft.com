<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeadAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lead $lead) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Lead assigned to you: '.$this->lead->reference)
            ->greeting('Hello '.$notifiable->name.',')
            ->line("The lead **{$this->lead->name}** ({$this->lead->reference}) has been assigned to you.")
            ->lineIf((bool) $this->lead->service?->name, 'Service: '.$this->lead->service?->name)
            ->lineIf((bool) $this->lead->budget, 'Budget: '.$this->lead->budget)
            ->action('Open Lead', url('/admin/leads/'.$this->lead->id.'/edit'))
            ->line('Please follow up as soon as possible.');
    }
}

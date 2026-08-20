<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Mail\TicketAdminNotification;
use App\Mail\TicketReplyMail;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TicketService
{
    public function open(User $user, string $subject, string $message, TicketPriority $priority = TicketPriority::Normal): Ticket
    {
        $ticket = DB::transaction(function () use ($user, $subject, $message, $priority): Ticket {
            $ticket = Ticket::create([
                'reference' => Ticket::generateReference(),
                'user_id' => $user->id,
                'subject' => $subject,
                'priority' => $priority,
                'status' => TicketStatus::Open,
                'last_reply_at' => now(),
            ]);

            $ticket->messages()->create([
                'user_id' => $user->id,
                'is_staff' => false,
                'message' => $message,
            ]);

            return $ticket;
        });

        $this->notifyAdmins($ticket);

        return $ticket;
    }

    public function reply(Ticket $ticket, User $author, string $message, bool $isStaff): TicketMessage
    {
        $reply = $ticket->messages()->create([
            'user_id' => $author->id,
            'is_staff' => $isStaff,
            'message' => $message,
        ]);

        $ticket->update([
            'status' => $isStaff ? TicketStatus::Answered : TicketStatus::CustomerReply,
            'last_reply_at' => now(),
            'closed_at' => null,
        ]);

        if ($isStaff) {
            try {
                Mail::to($ticket->user->email)->queue(new TicketReplyMail($ticket, $reply));
            } catch (\Throwable $e) {
                Log::warning('Ticket reply email failed: '.$e->getMessage(), ['ticket' => $ticket->reference]);
            }
        } else {
            $this->notifyAdmins($ticket);
        }

        return $reply;
    }

    public function close(Ticket $ticket): void
    {
        $ticket->update([
            'status' => TicketStatus::Closed,
            'closed_at' => now(),
        ]);
    }

    protected function notifyAdmins(Ticket $ticket): void
    {
        try {
            foreach ($this->notificationRecipients() as $recipient) {
                Mail::to($recipient)->queue(new TicketAdminNotification($ticket));
            }
        } catch (\Throwable $e) {
            Log::warning('Ticket admin notification failed: '.$e->getMessage(), ['ticket' => $ticket->reference]);
        }
    }

    /** @return array<int, string> */
    public function notificationRecipients(): array
    {
        $configured = (string) settings('ticket_notification_recipients', settings('contact_form_recipients', ''));

        return collect(explode(',', $configured))
            ->map(fn (string $email) => trim($email))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();
    }
}

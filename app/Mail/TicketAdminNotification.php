<?php

namespace App\Mail;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketAdminNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function envelope(): Envelope
    {
        $prefix = $this->ticket->status === TicketStatus::CustomerReply ? 'Ticket reply' : 'New ticket';

        return new Envelope(
            subject: $prefix.': '.$this->ticket->subject.' — '.$this->ticket->reference,
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.ticket-admin-notification');
    }
}

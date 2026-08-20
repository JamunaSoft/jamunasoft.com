<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket, public TicketMessage $reply) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Re: '.$this->ticket->subject.' — '.$this->ticket->reference,
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.ticket-reply');
    }
}

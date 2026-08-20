<?php

namespace App\Mail;

use App\Models\DomainOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DomainOrderCompleted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public DomainOrder $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your domain '.$this->order->domain_name.' is ready — '.$this->order->reference,
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.domain-order-completed');
    }
}

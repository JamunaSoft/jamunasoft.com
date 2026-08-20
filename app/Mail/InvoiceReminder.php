<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        $overdue = $this->invoice->isOverdue();

        return new Envelope(
            subject: ($overdue ? 'Overdue invoice ' : 'Payment reminder — ').$this->invoice->reference,
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.invoice-reminder');
    }
}

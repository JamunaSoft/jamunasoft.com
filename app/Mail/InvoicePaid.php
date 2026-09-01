<?php

namespace App\Mail;

use App\Mail\Concerns\AttachesInvoicePdf;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePaid extends Mailable implements ShouldQueue
{
    use AttachesInvoicePdf, Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment received — '.$this->invoice->reference,
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.invoice-paid');
    }
}

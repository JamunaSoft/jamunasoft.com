<?php

namespace App\Mail;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationResponded extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Quotation $quotation) {}

    public function envelope(): Envelope
    {
        $verb = $this->quotation->status === QuotationStatus::Accepted ? 'accepted' : 'declined';

        return new Envelope(
            subject: 'Quotation '.$verb.': '.$this->quotation->reference.' — '.$this->quotation->customer_name,
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.quotation-responded');
    }
}

<?php

namespace App\Mail;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DomainRenewalReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Domain $domain, public int $daysLeft) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->domain->name.' expires in '.$this->daysLeft.' '.str('day')->plural($this->daysLeft).' — renew now',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.domain-renewal-reminder');
    }
}

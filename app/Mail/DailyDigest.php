<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyDigest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @param  array<string, mixed>  $data */
    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Daily summary — '.now()->format('d M Y')
                .' · ৳'.number_format($this->data['paymentsTotal'] ?? 0, 0).' received'
                .(($this->data['failedJobs'] ?? 0) > 0 ? ' · ⚠ queue issues' : ''),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.daily-digest');
    }
}

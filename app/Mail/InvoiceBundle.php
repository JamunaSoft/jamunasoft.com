<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\InvoicePdfRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Several invoices for the same recipients in ONE email — e.g. one owner
 * whose two companies were billed together. Each invoice's PDF is attached.
 */
class InvoiceBundle extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @param  array<int, Invoice>  $invoices */
    public function __construct(public array $invoices) {}

    public function envelope(): Envelope
    {
        $total = collect($this->invoices)->sum(fn (Invoice $invoice) => $invoice->balance());

        return new Envelope(
            subject: count($this->invoices).' invoices from '.settings('company_name', config('app.name'))
                .' — total ৳'.number_format($total, 2),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.invoice-bundle');
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        $renderer = app(InvoicePdfRenderer::class);

        return collect($this->invoices)
            ->map(fn (Invoice $invoice) => Attachment::fromData(
                fn () => $renderer->render($invoice)->output(),
                $renderer->filename($invoice),
            )->withMime('application/pdf'))
            ->all();
    }
}

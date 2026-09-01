<?php

namespace App\Mail\Concerns;

use App\Models\Invoice;
use App\Services\InvoicePdfRenderer;
use Illuminate\Mail\Mailables\Attachment;

/**
 * Attaches the invoice PDF to the mailable. The PDF is rendered lazily at
 * send time (in the queue worker), so it always reflects the current
 * status, payments and settings.
 *
 * @property Invoice $invoice
 */
trait AttachesInvoicePdf
{
    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        $renderer = app(InvoicePdfRenderer::class);

        return [
            Attachment::fromData(
                fn () => $renderer->render($this->invoice)->output(),
                $renderer->filename($this->invoice),
            )->withMime('application/pdf'),
        ];
    }
}

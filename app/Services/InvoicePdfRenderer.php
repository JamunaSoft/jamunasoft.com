<?php

namespace App\Services;

use App\Models\ClientService;
use App\Models\Domain;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class InvoicePdfRenderer
{
    public function render(Invoice $invoice): PdfDocument
    {
        $invoice->load(['user', 'items', 'payments' => fn ($query) => $query->orderBy('paid_at')]);

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'logo' => $this->logoDataUri(),
            'expiries' => $this->itemExpiries($invoice),
        ])->setPaper('a4');
    }

    public function filename(Invoice $invoice): string
    {
        return $invoice->reference.'.pdf';
    }

    /**
     * The uploaded brand logo as a data URI so DomPDF needs no file access.
     */
    public function logoDataUri(): ?string
    {
        $path = null;
        $setting = settings('logo_path');

        if ($setting && Storage::disk('public')->exists($setting)) {
            $path = Storage::disk('public')->path($setting);
        } elseif (is_file(public_path('assets/logo.png'))) {
            $path = public_path('assets/logo.png');
        }

        if ($path === null) {
            return null;
        }

        $mime = str_ends_with(strtolower($path), '.svg') ? 'image/svg+xml' : mime_content_type($path);

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }

    /**
     * Expiry date per invoice item id, resolved from whatever the line bills:
     * services expire at their next due date, domains at their registry expiry.
     *
     * @return array<int, Carbon>
     */
    protected function itemExpiries(Invoice $invoice): array
    {
        $expiries = [];

        foreach ($invoice->items as $item) {
            if ($item->item_id === null) {
                continue;
            }

            $expiry = match ($item->item_type) {
                'client_service' => ClientService::find($item->item_id)?->next_due_at,
                'domain' => Domain::find($item->item_id)?->expires_at,
                default => null,
            };

            if ($expiry !== null) {
                $expiries[$item->id] = $expiry;
            }
        }

        return $expiries;
    }
}

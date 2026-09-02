<?php

namespace App\Services;

use App\Models\ClientService;
use App\Models\Domain;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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
        ])->setPaper('a4')->setOption('isFontSubsettingEnabled', true);
    }

    public function filename(Invoice $invoice): string
    {
        return $invoice->reference.'.pdf';
    }

    /**
     * The uploaded brand logo as a data URI so DomPDF needs no file access.
     * The image is downscaled to display size (it renders ~150px wide) and
     * cached — a multi-hundred-KB upload would otherwise bloat every PDF.
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

        if (str_ends_with(strtolower($path), '.svg')) {
            return 'data:image/svg+xml;base64,'.base64_encode((string) file_get_contents($path));
        }

        return Cache::remember(
            'invoice_logo_data_uri:'.md5($path.'|'.filemtime($path)),
            now()->addDay(),
            function () use ($path): string {
                $raw = (string) file_get_contents($path);
                $fallback = 'data:'.mime_content_type($path).';base64,'.base64_encode($raw);

                if (! function_exists('imagecreatefromstring')) {
                    return $fallback;
                }

                $source = @imagecreatefromstring($raw);

                if ($source === false) {
                    return $fallback;
                }

                // 2x the ~150px render width keeps it crisp in print.
                if (imagesx($source) > 400) {
                    $scaled = imagescale($source, 400);

                    if ($scaled !== false) {
                        imagedestroy($source);
                        $source = $scaled;
                    }
                }

                imagesavealpha($source, true);

                ob_start();
                imagepng($source, null, 9);
                $png = (string) ob_get_clean();
                imagedestroy($source);

                return strlen($png) < strlen($raw)
                    ? 'data:image/png;base64,'.base64_encode($png)
                    : $fallback;
            },
        );
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

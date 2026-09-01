<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\InvoicePdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvoicePdfController extends Controller
{
    public function __invoke(Request $request, Invoice $invoice, InvoicePdfRenderer $renderer): Response
    {
        $user = $request->user();
        $isStaff = $user->roles()->exists();

        abort_unless($isStaff || $invoice->user_id === $user->id, 403);

        // Clients never see drafts (mirrors the client panel's invoice list).
        abort_if(! $isStaff && $invoice->status === InvoiceStatus::Draft, 403);

        $pdf = $renderer->render($invoice);
        $filename = $renderer->filename($invoice);

        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }
}

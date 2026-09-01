<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\InvoicePdfRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class InvoiceViewController extends Controller
{
    public function show(string $reference, string $token): View
    {
        $invoice = $this->resolve($reference, $token);

        return view('invoices.show', [
            'invoice' => $invoice->load(['user', 'items', 'payments' => fn ($query) => $query->orderBy('paid_at')]),
            'seo' => [
                'title' => __('Invoice :reference', ['reference' => $reference]),
                'description' => __('View and pay your invoice online.'),
            ],
        ]);
    }

    public function pdf(InvoicePdfRenderer $renderer, string $reference, string $token): Response
    {
        $invoice = $this->resolve($reference, $token);

        return $renderer->render($invoice)->download($renderer->filename($invoice));
    }

    protected function resolve(string $reference, string $token): Invoice
    {
        return Invoice::query()
            ->where('reference', $reference)
            ->where('token', $token)
            ->where('status', '!=', InvoiceStatus::Draft)
            ->firstOrFail();
    }
}

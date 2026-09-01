<?php

namespace App\Services;

use App\Enums\QuotationStatus;
use App\Mail\QuotationResponded;
use App\Mail\QuotationSent;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class QuotationService
{
    public function __construct(protected InvoiceService $invoices) {}

    /**
     * Recompute item totals and quotation totals; call after items change.
     */
    public function recalculateTotals(Quotation $quotation): Quotation
    {
        $subtotal = 0.0;

        foreach ($quotation->items()->get() as $item) {
            $total = round((float) $item->quantity * (float) $item->unit_price, 2);

            if ((float) $item->total !== $total) {
                $item->update(['total' => $total]);
            }

            $subtotal += $total;
        }

        $quotation->update([
            'subtotal' => round($subtotal, 2),
            'total' => round($subtotal - (float) $quotation->discount, 2),
        ]);

        return $quotation->refresh();
    }

    /**
     * Email the quotation to the customer with its public link.
     */
    public function send(Quotation $quotation): void
    {
        $quotation->update([
            'status' => QuotationStatus::Sent,
            'sent_at' => now(),
        ]);

        try {
            Mail::to($quotation->customer_email)->queue(new QuotationSent($quotation));
        } catch (\Throwable $e) {
            Log::warning('Quotation email failed: '.$e->getMessage(), ['quotation' => $quotation->reference]);
        }
    }

    /**
     * Customer response from the public quotation page.
     */
    public function respond(Quotation $quotation, bool $accepted): void
    {
        $quotation->update([
            'status' => $accepted ? QuotationStatus::Accepted : QuotationStatus::Declined,
            'responded_at' => now(),
        ]);

        try {
            foreach ($this->notificationRecipients() as $recipient) {
                Mail::to($recipient)->queue(new QuotationResponded($quotation));
            }
        } catch (\Throwable $e) {
            Log::warning('Quotation response notification failed: '.$e->getMessage(), ['quotation' => $quotation->reference]);
        }
    }

    /**
     * Turn an accepted quotation into an invoice, creating the client
     * account by email when it does not exist yet.
     */
    public function convertToInvoice(Quotation $quotation): Invoice
    {
        $user = $quotation->user ?? User::firstOrCreate(
            ['email' => $quotation->customer_email],
            ['name' => $quotation->customer_name, 'password' => str()->password(32)],
        );

        $invoice = $this->invoices->create(
            userId: $user->id,
            items: $quotation->items->map(fn ($item) => [
                'title' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])->all(),
            notes: 'Quotation '.$quotation->reference,
        );

        if ((float) $quotation->discount > 0) {
            $invoice->update(['discount' => $quotation->discount]);
            $invoice = $this->invoices->recalculateTotals($invoice);
        }

        $quotation->update(['user_id' => $user->id, 'invoice_id' => $invoice->id]);

        return $invoice;
    }

    /** @return array<int, string> */
    public function notificationRecipients(): array
    {
        $configured = (string) settings('lead_notification_recipients', settings('contact_form_recipients', ''));

        return collect(explode(',', $configured))
            ->map(fn (string $email) => trim($email))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();
    }
}

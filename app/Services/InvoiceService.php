<?php

namespace App\Services;

use App\Enums\ClientServiceStatus;
use App\Enums\DomainOrderStatus;
use App\Enums\InvoiceStatus;
use App\Mail\InvoiceBundle;
use App\Mail\InvoiceCreated;
use App\Mail\InvoicePaid;
use App\Mail\InvoiceReminder;
use App\Models\ClientService;
use App\Models\DomainOrder;
use App\Models\EmailLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvoiceService
{
    /**
     * Create an invoice with line items. Each item has a title (the bold
     * line on the invoice) and an optional multiline description.
     *
     * @param  array<int, array{title?: string, description?: ?string, quantity?: float, unit_price: float, item_type?: ?string, item_id?: ?int}>  $items
     */
    public function create(
        int $userId,
        array $items,
        ?\DateTimeInterface $dueAt = null,
        ?string $notes = null,
        bool $sendEmail = true,
        ?int $billingProfileId = null,
    ): Invoice {
        $invoice = DB::transaction(function () use ($userId, $items, $dueAt, $notes, $billingProfileId): Invoice {
            $invoice = Invoice::create([
                'reference' => Invoice::generateReference(),
                'user_id' => $userId,
                'billing_profile_id' => $billingProfileId,
                'status' => InvoiceStatus::Unpaid,
                'due_at' => $dueAt ?? now()->addDays(7),
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                $quantity = (float) ($item['quantity'] ?? 1);

                $invoice->items()->create([
                    'title' => $item['title'] ?? null,
                    'description' => $item['description'] ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $item['unit_price'],
                    'total' => round($quantity * (float) $item['unit_price'], 2),
                    'item_type' => $item['item_type'] ?? null,
                    'item_id' => $item['item_id'] ?? null,
                ]);
            }

            return $this->recalculateTotals($invoice);
        });

        if ($sendEmail) {
            $this->sendInvoice($invoice);
        }

        return $invoice;
    }

    /**
     * Recompute item totals and invoice totals; call after items change.
     */
    public function recalculateTotals(Invoice $invoice): Invoice
    {
        $subtotal = 0.0;

        foreach ($invoice->items()->get() as $item) {
            $total = round((float) $item->quantity * (float) $item->unit_price, 2);

            if ((float) $item->total !== $total) {
                $item->update(['total' => $total]);
            }

            $subtotal += $total;
        }

        $invoice->update([
            'subtotal' => round($subtotal, 2),
            'total' => round($subtotal - (float) $invoice->discount, 2),
        ]);

        return $invoice->refresh();
    }

    public function sendInvoice(Invoice $invoice): void
    {
        try {
            $mail = new InvoiceCreated($invoice);
            $recipients = $invoice->recipients();
            Mail::to($recipients)
                ->bcc(config('mail.billing_bcc'))
                ->queue($mail);
            $this->logEmail($invoice, 'invoice_created', $mail->envelope()->subject, implode(', ', $recipients));
        } catch (\Throwable $e) {
            Log::warning('Invoice email failed: '.$e->getMessage(), ['invoice' => $invoice->reference]);
        }
    }

    /**
     * Record a payment. When the invoice is fully paid, mark it paid and run
     * the side effects for whatever the line items bill (domain orders start
     * registrar processing, services roll their next due date forward).
     */
    public function recordPayment(
        Invoice $invoice,
        float $amount,
        ?string $method = null,
        ?string $transactionId = null,
        ?int $recordedBy = null,
        bool $processSideEffects = true,
    ): Payment {
        $payment = $invoice->payments()->create([
            'user_id' => $invoice->user_id,
            'amount' => $amount,
            'method' => $method,
            'transaction_id' => $transactionId,
            'paid_at' => now(),
            'recorded_by' => $recordedBy,
        ]);

        $invoice->update(['amount_paid' => round((float) $invoice->amount_paid + $amount, 2)]);

        if ($invoice->balance() <= 0 && $invoice->status->isOpen()) {
            $invoice->update(['status' => InvoiceStatus::Paid, 'paid_at' => now()]);

            if ($processSideEffects) {
                $this->runPaidSideEffects($invoice, $method, $transactionId);
            }

            try {
                $mail = new InvoicePaid($invoice);
                $recipients = $invoice->recipients();
                Mail::to($recipients)
                    ->bcc(config('mail.billing_bcc'))
                    ->queue($mail);
                $this->logEmail($invoice, 'invoice_paid', $mail->envelope()->subject, implode(', ', $recipients));
            } catch (\Throwable $e) {
                Log::warning('Invoice receipt email failed: '.$e->getMessage(), ['invoice' => $invoice->reference]);
            }
        }

        return $payment;
    }

    /**
     * One email carrying several invoices (each PDF attached) — used when
     * multiple invoices for the same recipients are generated together,
     * e.g. one owner with two companies.
     *
     * @param  Collection<int, Invoice>  $invoices
     */
    public function sendBundle(Collection $invoices): void
    {
        $invoices = $invoices->values();

        if ($invoices->count() === 1) {
            $this->sendInvoice($invoices->first());

            return;
        }

        try {
            $recipients = $invoices->first()->recipients();
            $mail = new InvoiceBundle($invoices->all());

            Mail::to($recipients)
                ->bcc(config('mail.billing_bcc'))
                ->queue($mail);

            foreach ($invoices as $invoice) {
                $this->logEmail($invoice, 'invoice_created', $mail->envelope()->subject, implode(', ', $recipients));
            }
        } catch (\Throwable $e) {
            Log::warning('Invoice bundle email failed: '.$e->getMessage(), [
                'invoices' => $invoices->pluck('reference')->all(),
            ]);
        }
    }

    /**
     * Queue a payment-reminder email (both billing inboxes, BCC office) and
     * stamp last_reminded_at. Throws on failure so callers can react.
     */
    public function sendReminder(Invoice $invoice): void
    {
        $mail = new InvoiceReminder($invoice);
        $recipients = $invoice->recipients();

        Mail::to($recipients)
            ->bcc(config('mail.billing_bcc'))
            ->queue($mail);

        $this->logEmail($invoice, 'invoice_reminder', $mail->envelope()->subject, implode(', ', $recipients));
        $invoice->update(['last_reminded_at' => now()]);
    }

    /**
     * Copy an invoice into a fresh unpaid one — same items (including their
     * service/domain links), discount and notes. Not emailed automatically.
     */
    public function duplicate(Invoice $invoice): Invoice
    {
        $copy = $this->create(
            userId: $invoice->user_id,
            items: $invoice->items->map(fn (InvoiceItem $item) => [
                'title' => $item->title,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'item_type' => $item->item_type,
                'item_id' => $item->item_id,
            ])->all(),
            notes: $invoice->notes,
            sendEmail: false,
        );

        if ((float) $invoice->discount > 0) {
            $copy->update(['discount' => $invoice->discount]);
            $copy = $this->recalculateTotals($copy);
        }

        return $copy;
    }

    /**
     * WHMCS-style merge: combine several unpaid invoices of one client into
     * the oldest one. Items and payments move to the merged invoice, the
     * emptied invoices are cancelled with an audit note, and no email is
     * sent — the admin reviews the result and emails it manually.
     *
     * @param  Collection<int, Invoice>  $invoices
     */
    public function merge(Collection $invoices): Invoice
    {
        if ($invoices->count() < 2) {
            throw new \InvalidArgumentException('Select at least two invoices to merge.');
        }

        if ($invoices->pluck('user_id')->unique()->count() > 1) {
            throw new \InvalidArgumentException('Only invoices of the same client can be merged.');
        }

        if ($invoices->contains(fn (Invoice $invoice) => $invoice->status !== InvoiceStatus::Unpaid)) {
            throw new \InvalidArgumentException('Only unpaid invoices can be merged.');
        }

        return DB::transaction(function () use ($invoices): Invoice {
            $sorted = $invoices->sortBy([['created_at', 'asc'], ['id', 'asc']])->values();

            /** @var Invoice $target */
            $target = $sorted->first();
            $sources = $sorted->slice(1);

            $combinedDiscount = round($sorted->sum(fn (Invoice $invoice) => (float) $invoice->discount), 2);
            $combinedPaid = round($sorted->sum(fn (Invoice $invoice) => (float) $invoice->amount_paid), 2);
            $earliestDue = $sorted->pluck('due_at')->filter()->min();
            $mergedReferences = $sources->pluck('reference')->implode(', ');

            foreach ($sources as $source) {
                $source->items()->update(['invoice_id' => $target->id]);
                $source->payments()->update(['invoice_id' => $target->id]);

                $originalTotal = number_format((float) $source->total, 2);
                $source->update([
                    'status' => InvoiceStatus::Cancelled,
                    'subtotal' => 0,
                    'discount' => 0,
                    'total' => 0,
                    'amount_paid' => 0,
                    'notes' => trim(($source->notes ? $source->notes."\n" : '')
                        ."Merged into {$target->reference} on ".now()->format('d M Y')." (original total ৳{$originalTotal})."),
                ]);
            }

            $target->update([
                'discount' => $combinedDiscount,
                'amount_paid' => $combinedPaid,
                'due_at' => $earliestDue,
                'notes' => trim(($target->notes ? $target->notes."\n" : '')."Merged with: {$mergedReferences}."),
            ]);

            $target = $this->recalculateTotals($target);

            // Carried-over partial payments can cover the combined total.
            if ($target->balance() <= 0) {
                $target->update(['status' => InvoiceStatus::Paid, 'paid_at' => now()]);
            }

            return $target->refresh();
        });
    }

    public function logEmail(Invoice $invoice, string $type, string $subject, string $recipient): void
    {
        EmailLog::create([
            'user_id' => $invoice->user_id,
            'type' => $type,
            'subject' => $subject,
            'recipient' => $recipient,
            'bcc' => config('mail.billing_bcc'),
            'status' => 'queued',
            'related_type' => Invoice::class,
            'related_id' => $invoice->id,
            'queued_at' => now(),
        ]);
    }

    protected function runPaidSideEffects(Invoice $invoice, ?string $method, ?string $transactionId): void
    {
        foreach ($invoice->items as $item) {
            if ($item->item_type === 'domain_order' && $item->item_id !== null) {
                $order = DomainOrder::find($item->item_id);

                if ($order !== null && $order->status === DomainOrderStatus::PendingPayment) {
                    // settleInvoice=false — this invoice is already settled.
                    app(DomainOrderService::class)->markPaid($order, $method, $transactionId, settleInvoice: false);
                }
            }

            if ($item->item_type === 'client_service' && $item->item_id !== null) {
                $service = ClientService::find($item->item_id);

                if ($service !== null) {
                    $service->update([
                        'status' => ClientServiceStatus::Active,
                        'next_due_at' => $service->billing_cycle->advance(
                            $service->next_due_at ?? now()->startOfDay(),
                        ),
                    ]);
                }
            }
        }
    }

    /**
     * The open invoice linked to a billable record, if any.
     */
    public function openInvoiceFor(string $itemType, int $itemId): ?Invoice
    {
        return Invoice::query()
            ->open()
            ->whereHas('items', fn ($query) => $query
                ->where('item_type', $itemType)
                ->where('item_id', $itemId))
            ->latest()
            ->first();
    }
}

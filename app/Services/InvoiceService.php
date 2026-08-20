<?php

namespace App\Services;

use App\Enums\ClientServiceStatus;
use App\Enums\DomainOrderStatus;
use App\Enums\InvoiceStatus;
use App\Mail\InvoiceCreated;
use App\Mail\InvoicePaid;
use App\Models\ClientService;
use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvoiceService
{
    /**
     * Create an invoice with line items.
     *
     * @param  array<int, array{description: string, quantity?: float, unit_price: float, item_type?: ?string, item_id?: ?int}>  $items
     */
    public function create(
        int $userId,
        array $items,
        ?\DateTimeInterface $dueAt = null,
        ?string $notes = null,
        bool $sendEmail = true,
    ): Invoice {
        $invoice = DB::transaction(function () use ($userId, $items, $dueAt, $notes): Invoice {
            $invoice = Invoice::create([
                'reference' => Invoice::generateReference(),
                'user_id' => $userId,
                'status' => InvoiceStatus::Unpaid,
                'due_at' => $dueAt ?? now()->addDays(7),
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                $quantity = (float) ($item['quantity'] ?? 1);

                $invoice->items()->create([
                    'description' => $item['description'],
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
            Mail::to($invoice->user->email)->queue(new InvoiceCreated($invoice));
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
                Mail::to($invoice->user->email)->queue(new InvoicePaid($invoice));
            } catch (\Throwable $e) {
                Log::warning('Invoice receipt email failed: '.$e->getMessage(), ['invoice' => $invoice->reference]);
            }
        }

        return $payment;
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

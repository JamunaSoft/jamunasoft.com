<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\ClientService;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class RecurringBillingService
{
    /** Invoice this many days before a service's next due date. */
    public const INVOICE_AHEAD_DAYS = 7;

    /** Nag an unpaid invoice at most every this many days. */
    public const REMINDER_INTERVAL_DAYS = 3;

    public function __construct(protected InvoiceService $invoices) {}

    /**
     * Generate invoices for active services whose next due date is within the
     * look-ahead window. Idempotent: a service with an open invoice is skipped.
     *
     * @return array<int, Invoice>
     */
    public function generateDueInvoices(): array
    {
        $generated = [];

        ClientService::query()
            ->active()
            ->whereNotNull('next_due_at')
            ->where('next_due_at', '<=', now()->addDays(self::INVOICE_AHEAD_DAYS))
            ->with('user')
            ->each(function (ClientService $service) use (&$generated) {
                if ($service->hasOpenInvoice()) {
                    return;
                }

                $generated[] = $this->invoices->create(
                    userId: $service->user_id,
                    items: [[
                        'title' => sprintf(
                            '%s — %s (due %s)',
                            $service->name,
                            $service->billing_cycle->getLabel(),
                            $service->next_due_at->format('d M Y'),
                        ),
                        'unit_price' => (float) $service->price,
                        'item_type' => 'client_service',
                        'item_id' => $service->id,
                    ]],
                    dueAt: $service->next_due_at,
                );
            });

        return $generated;
    }

    /**
     * Email reminders for unpaid invoices that are due soon or overdue.
     *
     * @return int number of reminders sent
     */
    public function sendReminders(): int
    {
        $sent = 0;

        Invoice::query()
            ->where('status', InvoiceStatus::Unpaid)
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->addDays(self::REMINDER_INTERVAL_DAYS))
            ->where(fn ($query) => $query
                ->whereNull('last_reminded_at')
                ->orWhere('last_reminded_at', '<=', now()->subDays(self::REMINDER_INTERVAL_DAYS)))
            ->with('user')
            ->each(function (Invoice $invoice) use (&$sent) {
                try {
                    $this->invoices->sendReminder($invoice);
                } catch (\Throwable $e) {
                    Log::warning('Invoice reminder failed: '.$e->getMessage(), ['invoice' => $invoice->reference]);

                    return;
                }

                $sent++;
            });

        return $sent;
    }
}

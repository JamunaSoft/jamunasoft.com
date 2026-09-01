<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Collection;
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
     * look-ahead window. All of a client's due services are consolidated into
     * ONE invoice (one line item each) — paying it advances every service's
     * next due date. Idempotent: a service with an open invoice is skipped.
     *
     * @return array<int, Invoice>
     */
    public function generateDueInvoices(): array
    {
        $generated = [];

        $dueServices = ClientService::query()
            ->active()
            ->whereNotNull('next_due_at')
            ->where('next_due_at', '<=', now()->addDays(self::INVOICE_AHEAD_DAYS))
            ->with('user')
            ->get()
            ->reject(fn (ClientService $service) => $service->hasOpenInvoice());

        foreach ($dueServices->groupBy('user_id') as $services) {
            $generated[] = $this->invoices->create(
                userId: $services->first()->user_id,
                items: $this->serviceLineItems($services),
                dueAt: $services->min('next_due_at'),
            );
        }

        return $generated;
    }

    /**
     * One consolidated invoice for ALL of a client's active services that are
     * not already on an open invoice — regardless of the look-ahead window.
     * Used by the "Invoice all services" button on the client profile.
     * Not emailed automatically; returns null when there is nothing to bill.
     */
    public function invoiceAllServicesFor(User $user): ?Invoice
    {
        $services = ClientService::query()
            ->active()
            ->where('user_id', $user->id)
            ->whereNotNull('next_due_at')
            ->get()
            ->reject(fn (ClientService $service) => $service->hasOpenInvoice());

        if ($services->isEmpty()) {
            return null;
        }

        return $this->invoices->create(
            userId: $user->id,
            items: $this->serviceLineItems($services),
            dueAt: $services->min('next_due_at'),
            sendEmail: false,
        );
    }

    /**
     * @param  Collection<int, ClientService>  $services
     * @return array<int, array<string, mixed>>
     */
    protected function serviceLineItems(Collection $services): array
    {
        return $services
            ->sortBy('next_due_at')
            ->map(fn (ClientService $service) => [
                'title' => sprintf(
                    '%s — %s (due %s)',
                    $service->name,
                    $service->billing_cycle->getLabel(),
                    $service->next_due_at->format('d M Y'),
                ),
                'description' => $service->domain ? 'Domain: '.$service->domain : null,
                'unit_price' => (float) $service->price,
                'item_type' => 'client_service',
                'item_id' => $service->id,
            ])
            ->values()
            ->all();
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

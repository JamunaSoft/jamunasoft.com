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
    /** Default days-before-due to invoice; the invoice_ahead_days setting overrides it. */
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

        $aheadDays = max(1, (int) settings('invoice_ahead_days', self::INVOICE_AHEAD_DAYS));
        $window = now()->addDays($aheadDays);

        // Monthly batch day: on this day of the month, bill everything due in
        // the next 31 days at once — so every client's renewal goes out the
        // same day (e.g. the 1st) no matter when in the month it is due. On
        // other days the lead-time window still runs as a safety net for
        // services added mid-month.
        $batchDay = (int) settings('invoice_generation_day', 0);

        if ($batchDay >= 1 && now()->day === $batchDay) {
            $window = max($window, now()->addDays(31));
        }

        $dueServices = ClientService::query()
            ->active()
            ->whereNotNull('next_due_at')
            ->where('next_due_at', '<=', $window)
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
     * The invoice line item for one service: title "Name — Cycle" plus a
     * description built from the service's spec text, its domain, and the
     * billing period covered (computed from next due date + cycle).
     *
     * @return array<string, mixed>
     */
    public function lineItemFor(ClientService $service): array
    {
        $lines = [];

        if (filled($service->description)) {
            $lines[] = trim($service->description);
        }

        if (filled($service->domain)) {
            $lines[] = 'Domain: '.$service->domain;
        }

        if ($service->next_due_at !== null) {
            $start = $service->next_due_at;
            $end = $service->billing_cycle->advance($start)->subDay();
            $lines[] = sprintf('Duration: %s – %s', $start->format('M d, Y'), $end->format('M d, Y'));
        }

        return [
            'title' => sprintf('%s — %s', $service->name, $service->billing_cycle->getLabel()),
            'description' => $lines !== [] ? implode("\n", $lines) : null,
            'unit_price' => (float) $service->price,
            'item_type' => 'client_service',
            'item_id' => $service->id,
        ];
    }

    /**
     * @param  Collection<int, ClientService>  $services
     * @return array<int, array<string, mixed>>
     */
    protected function serviceLineItems(Collection $services): array
    {
        return $services
            ->sortBy('next_due_at')
            ->map(fn (ClientService $service) => $this->lineItemFor($service))
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

<?php

namespace App\Console\Commands;

use App\Services\RecurringBillingService;
use Illuminate\Console\Command;

class SendInvoiceReminders extends Command
{
    protected $signature = 'billing:send-invoice-reminders';

    protected $description = 'Email reminders for unpaid invoices that are due soon or overdue';

    public function handle(RecurringBillingService $billing): int
    {
        $sent = $billing->sendReminders();

        $this->info("Sent {$sent} invoice ".str('reminder')->plural($sent).'.');

        return self::SUCCESS;
    }
}

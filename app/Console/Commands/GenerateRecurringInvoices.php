<?php

namespace App\Console\Commands;

use App\Services\RecurringBillingService;
use Illuminate\Console\Command;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'billing:generate-invoices';

    protected $description = 'Generate invoices for recurring services approaching their due date';

    public function handle(RecurringBillingService $billing): int
    {
        $generated = $billing->generateDueInvoices();

        foreach ($generated as $invoice) {
            $this->line("Generated {$invoice->reference} for {$invoice->user->name} (৳{$invoice->total}).");
        }

        $this->info('Generated '.count($generated).' '.str('invoice')->plural(count($generated)).'.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\DomainOrder;
use App\Models\InvoiceItem;
use App\Services\DomainOrderService;
use Illuminate\Console\Command;

class BackfillRenewalDurations extends Command
{
    protected $signature = 'billing:backfill-renewal-durations
        {--commit : Actually write; without this flag only a preview is shown}';

    protected $description = 'Add the "Duration: …" line to existing UNPAID domain-renewal invoice items (paid ones are skipped — their domain expiry has already moved)';

    public function handle(DomainOrderService $orders): int
    {
        $commit = (bool) $this->option('commit');
        $rows = [];
        $updated = 0;

        InvoiceItem::query()
            ->whereNull('description')
            ->where('item_type', 'domain_order')
            ->whereHas('invoice', fn ($query) => $query->unpaid())
            ->with('invoice')
            ->each(function (InvoiceItem $item) use ($orders, $commit, &$rows, &$updated) {
                $order = DomainOrder::find($item->item_id);

                if ($order === null) {
                    return;
                }

                $line = $orders->renewalPeriodLine($order);

                if ($line === null) {
                    $rows[] = [$item->invoice->reference, $order->domain_name, 'skipped (not a renewal, or domain expiry unknown)'];

                    return;
                }

                if ($commit) {
                    $item->update(['description' => $line]);
                }

                $rows[] = [$item->invoice->reference, $order->domain_name, $line];
                $updated++;
            });

        $this->table(['Invoice', 'Domain', 'Duration '.($commit ? 'written' : 'to write')], $rows);
        $this->info("{$updated} items ".($commit ? 'updated' : 'would be updated').'.');

        if (! $commit) {
            $this->warn('Preview only — run again with --commit to write.');
        }

        return self::SUCCESS;
    }
}

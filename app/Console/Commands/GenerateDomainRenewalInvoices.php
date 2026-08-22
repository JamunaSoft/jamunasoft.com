<?php

namespace App\Console\Commands;

use App\Enums\DomainOrderType;
use App\Models\Domain;
use App\Models\DomainOrder;
use App\Models\Tld;
use App\Services\DomainOrderService;
use Illuminate\Console\Command;

class GenerateDomainRenewalInvoices extends Command
{
    /** Invoice this many days before a domain expires (WHMCS-style). */
    public const INVOICE_AHEAD_DAYS = 30;

    protected $signature = 'billing:generate-domain-renewal-invoices';

    protected $description = 'Create renewal orders + invoices for customer domains approaching expiry (when enabled in settings)';

    public function handle(DomainOrderService $orders): int
    {
        if (! filter_var(settings('domain_auto_invoice', false), FILTER_VALIDATE_BOOL)) {
            $this->line('Domain auto-invoicing is disabled in Website Settings — nothing to do.');

            return self::SUCCESS;
        }

        $generated = 0;

        Domain::query()
            ->whereNotNull('user_id')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(self::INVOICE_AHEAD_DAYS)])
            ->with('user')
            ->each(function (Domain $domain) use ($orders, &$generated) {
                if (Tld::matching($domain->name) === null) {
                    $this->warn("{$domain->name}: skipped — no active TLD pricing.");

                    return;
                }

                $hasOpenOrder = DomainOrder::query()
                    ->where('domain_name', $domain->name)
                    ->where('type', DomainOrderType::Renew)
                    ->open()
                    ->exists();

                if ($hasOpenOrder) {
                    return;
                }

                $order = $orders->create(
                    customer: ['name' => $domain->user->name, 'email' => $domain->user->email, 'user_id' => $domain->user_id],
                    domainName: $domain->name,
                    type: DomainOrderType::Renew,
                );

                $generated++;
                $this->line("Renewal order {$order->reference} created for {$domain->name} ({$domain->user->email}).");
            });

        $this->info("Generated {$generated} renewal ".str('order')->plural($generated).'.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\BillingCycle;
use App\Enums\ClientServiceStatus;
use App\Models\ClientService;
use App\Models\User;
use App\Services\WhmcsClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ImportWhmcsServices extends Command
{
    protected $signature = 'whmcs:import-services
        {--commit : Actually write; without this flag only a preview is shown}';

    protected $description = 'Import active WHMCS hosting products as recurring services (so they keep billing here)';

    protected const CYCLES = [
        'Monthly' => BillingCycle::Monthly,
        'Quarterly' => BillingCycle::Quarterly,
        'Semi-Annually' => BillingCycle::SemiAnnually,
        'Annually' => BillingCycle::Yearly,
        'Biennially' => BillingCycle::Biennially,
    ];

    public function handle(WhmcsClient $whmcs): int
    {
        $commit = (bool) $this->option('commit');

        $this->info('Fetching WHMCS clients and products…');
        $clientsById = collect($whmcs->allClients())->keyBy('id');
        $localByEmail = User::whereDoesntHave('roles')->get()->keyBy(fn (User $user) => strtolower($user->email));

        $rows = [];
        $imported = 0;
        $skipped = 0;

        foreach ($whmcs->allProducts() as $product) {
            $name = trim((string) ($product['translated_name'] ?? '') ?: (string) $product['name']);
            $domain = strtolower(trim((string) ($product['domain'] ?? '')));
            $label = $name.($domain ? " ({$domain})" : '');

            if (($product['status'] ?? '') !== 'Active') {
                continue; // silently ignore cancelled/terminated products
            }

            $client = $clientsById->get((int) $product['clientid']);
            $local = $client ? $localByEmail->get(strtolower(trim((string) $client['email']))) : null;

            if ($local === null) {
                $rows[] = [$label, '—', 'no matching client'];
                $skipped++;

                continue;
            }

            $cycle = self::CYCLES[(string) ($product['billingcycle'] ?? '')] ?? null;
            $price = (float) ($product['recurringamount'] ?? 0);

            if ($cycle === null || $price <= 0) {
                $rows[] = [$label, $local->email, 'skipped ('.($product['billingcycle'] ?? 'no cycle').', ৳'.$price.')'];
                $skipped++;

                continue;
            }

            $exists = ClientService::where('user_id', $local->id)
                ->where('name', $name)
                ->where('domain', $domain ?: null)
                ->exists();

            if ($exists) {
                $rows[] = [$label, $local->email, 'already imported'];
                $skipped++;

                continue;
            }

            $nextDue = (string) ($product['nextduedate'] ?? '');
            $nextDueAt = ($nextDue !== '' && ! str_starts_with($nextDue, '0000')) ? Carbon::parse($nextDue) : null;

            $rows[] = [
                $label,
                $local->email,
                ($commit ? 'IMPORTED' : 'will import').' — '.$cycle->getLabel().' ৳'.number_format($price)
                    .' due '.($nextDueAt?->format('d M Y') ?? 'UNSET (fix manually!)'),
            ];
            $imported++;

            if ($commit) {
                ClientService::create([
                    'user_id' => $local->id,
                    'name' => $name,
                    'domain' => $domain ?: null,
                    'billing_cycle' => $cycle,
                    'price' => $price,
                    'status' => ClientServiceStatus::Active,
                    'next_due_at' => $nextDueAt,
                ]);
            }
        }

        $this->table(['Product', 'Client', 'Action'], $rows);
        $this->info("Services: {$imported} ".($commit ? 'imported' : 'to import').", {$skipped} skipped.");

        if (! $commit) {
            $this->warn('Preview only — run again with --commit to write.');
        }

        return self::SUCCESS;
    }
}

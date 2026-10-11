<?php

namespace App\Console\Commands;

use App\Services\TldPriceSyncService;
use App\Services\WhoisRetailPricing;
use App\Support\Settings;
use Illuminate\Console\Command;
use Throwable;

class SyncTldPrices extends Command
{
    protected $signature = 'domains:sync-tld-prices {--scheduled : Respect the automatic sync setting}';

    protected $description = 'Update USD/BDT and sync public Whois.com retail prices';

    public function handle(TldPriceSyncService $sync, WhoisRetailPricing $source): int
    {
        if ($this->option('scheduled') && ! filter_var(settings('tld_auto_price_sync', true), FILTER_VALIDATE_BOOL)) {
            $this->line('Automatic TLD pricing sync is disabled.');

            return self::SUCCESS;
        }
        try {
            $rate = $sync->updateExchangeRate();
            $sync->reprice($rate);
            $this->info('USD/BDT rate updated: '.$rate);
            $count = $sync->apply($source->fetch(), $rate);
            Settings::set(['tld_sync_error' => null], 'domains');
            $this->info("Updated {$count} TLD prices.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            Settings::set(['tld_sync_error' => $e->getMessage()], 'domains');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}

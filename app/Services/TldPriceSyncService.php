<?php

namespace App\Services;

use App\Models\Tld;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TldPriceSyncService
{
    public function updateExchangeRate(): float
    {
        $response = Http::timeout(20)->get('https://open.er-api.com/v6/latest/USD');
        $data = $response->json();
        $rate = data_get($data, 'rates.BDT');
        $timestamp = data_get($data, 'time_last_update_unix');
        if (! $response->successful() || data_get($data, 'result') !== 'success'
            || data_get($data, 'base_code') !== 'USD' || ! is_numeric($rate)
            || $rate <= 0 || $rate > 10000 || ! is_numeric($timestamp)
            || $timestamp < now()->subDays(3)->timestamp || $timestamp > now()->addHour()->timestamp) {
            throw new RuntimeException('Invalid or stale USD/BDT exchange rate. Existing prices were kept.');
        }
        Settings::set([
            'usd_bdt_rate' => $rate,
            'usd_bdt_updated_at' => now()->toIso8601String(),
        ], 'domains');

        return (float) $rate;
    }

    public function reprice(float $rate): int
    {
        $prices = [];
        foreach (Tld::query()->where('auto_price_sync', true)
            ->whereNotNull('register_usd')->whereNotNull('renew_usd')->whereNotNull('transfer_usd')->get() as $tld) {
            $prices[$tld->tld] = [
                'register' => (float) $tld->register_usd,
                'renew' => (float) $tld->renew_usd,
                'transfer' => (float) $tld->transfer_usd,
            ];
        }

        return $this->apply($prices, $rate, false);
    }

    public function apply(array $prices, float $rate, bool $freshSource = true): int
    {
        $margin = (float) settings('tld_margin_percent', 0);
        $fee = (float) settings('tld_exchange_buffer_percent', 0);
        $fixed = (float) settings('tld_margin_bdt', 0);
        if (! is_finite($rate) || $rate <= 0 || $margin < 0 || $fee < 0 || $fixed < 0) {
            throw new RuntimeException('Invalid exchange rate or pricing margin.');
        }
        // Validate the entire payload before making any changes.
        foreach ($prices as $tld => $entry) {
            if (! preg_match('/^[a-z0-9-]+(?:\.[a-z0-9-]+)*$/', $tld)) {
                throw new RuntimeException('Invalid TLD in pricing response.');
            }
            foreach (['register', 'renew', 'transfer'] as $action) {
                $usd = $entry[$action] ?? null;
                if (! is_numeric($usd) || ! is_finite((float) $usd) || $usd <= 0 || $this->convert((float) $usd, $rate, $fee, $margin, $fixed) > 99999999.99) {
                    throw new RuntimeException('Invalid retail price in pricing response.');
                }
            }
        }

        return DB::transaction(function () use ($prices, $rate, $fee, $margin, $fixed, $freshSource): int {
            $count = 0;
            foreach ($prices as $suffix => $entry) {
                $tld = Tld::firstOrNew(['tld' => $suffix]);
                // Existing manually maintained prices require explicit opt-in.
                if ($tld->exists && ! $tld->auto_price_sync) {
                    continue;
                }
                $data = ['auto_price_sync' => true];
                if ($freshSource) {
                    $data['prices_synced_at'] = now();
                }
                foreach ($entry as $action => $usd) {
                    $data[$action.'_usd'] = $usd;
                    $data[$action === 'register' ? 'register_price' : $action.'_price'] = $this->convert((float) $usd, $rate, $fee, $margin, $fixed);
                }
                // Keep new imports inactive until the admin confirms registrar support.
                $tld->fill($data)->save();
                $count++;
            }
            if ($freshSource) {
                Settings::set(['tld_prices_synced_at' => now()->toIso8601String()], 'domains');
            }

            return $count;
        });
    }

    private function convert(float $usd, float $rate, float $fee, float $margin, float $fixed): float
    {
        return ceil($usd * $rate * (1 + $fee / 100) * (1 + $margin / 100) + $fixed);
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\TldResource;
use App\Models\Domain;
use App\Models\Tld;
use App\Services\TldPriceSyncService;
use App\Services\WhoisRetailPricing;
use App\Support\Settings;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TldPriceSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Settings::flush();
        parent::tearDown();
    }

    private function pricesHtml(): string
    {
        return '<h1>Prices in USD</h1><table><tr><th>TLD</th><th>Registration</th><th>Renewal</th><th>Transfer</th></tr>'
            .'<tr><td>.org</td><td><del>$15.99</del>$9.99 / 1 year</td><td>$15.99</td><td>$12.99</td></tr>'
            .'<tr><td>.com.bd</td><td>$20.00</td><td>$25.00</td><td>$20.00</td></tr></table>';
    }

    public function test_public_parser_reads_promotional_registration_and_separate_renewal_prices(): void
    {
        $this->assertSame([
            'org' => ['register' => 9.99, 'renew' => 15.99, 'transfer' => 12.99],
            'com.bd' => ['register' => 20.0, 'renew' => 25.0, 'transfer' => 20.0],
        ], app(WhoisRetailPricing::class)->parse($this->pricesHtml()));
    }

    public function test_unrecognized_currency_or_ambiguous_or_multiyear_prices_are_rejected(): void
    {
        foreach ([str_replace('USD', 'EUR', $this->pricesHtml()),
            '<h1>USD</h1><table><tr><th>TLD</th><th>Registration</th><th>Renewal</th><th>Transfer</th></tr><tr><td>.org</td><td>$15.00 $9.99</td><td>$15.00</td><td>$15.00</td></tr></table>',
            str_replace('/ 1 year', '/ 2 years', str_replace('<tr><td>.com.bd</td><td>$20.00</td><td>$25.00</td><td>$20.00</td></tr>', '', $this->pricesHtml()))] as $html) {
            try {
                app(WhoisRetailPricing::class)->parse($html);
                $this->fail('Unsafe pricing was accepted.');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('prices were kept', $e->getMessage());
            }
        }
    }

    public function test_sync_converts_with_margin_and_preserves_manual_prices_and_custom_domain_prices(): void
    {
        Settings::set(['tld_margin_percent' => 10, 'tld_exchange_buffer_percent' => 2, 'tld_margin_bdt' => 50], 'domains');
        Tld::create(['tld' => 'manual', 'register_price' => 2000, 'renew_price' => 2200, 'transfer_price' => 1800, 'is_active' => true]);
        $domain = Domain::create(['name' => 'custom.org', 'renewal_price' => 1700]);
        $prices = app(WhoisRetailPricing::class)->parse($this->pricesHtml());
        $prices['manual'] = ['register' => 10, 'renew' => 10, 'transfer' => 10];
        $this->assertSame(2, app(TldPriceSyncService::class)->apply($prices, 120));
        $org = Tld::where('tld', 'org')->firstOrFail();
        $this->assertSame('1396.00', $org->register_price);
        $this->assertSame('9.9900', $org->register_usd);
        $this->assertFalse($org->is_active);
        $this->assertSame('2200.00', Tld::where('tld', 'manual')->firstOrFail()->renew_price);
        $this->assertSame(1700.0, $domain->refresh()->renewalPrice());
    }

    public function test_failed_public_fetch_keeps_usd_costs_but_updates_bdt_from_last_verified_costs(): void
    {
        $sync = app(TldPriceSyncService::class);
        $sync->apply(['org' => ['register' => 10, 'renew' => 15, 'transfer' => 12]], 100);
        $syncedAt = Tld::firstOrFail()->prices_synced_at;
        Http::preventStrayRequests();
        Http::fake([
            'open.er-api.com/*' => Http::response(['result' => 'success', 'base_code' => 'USD', 'rates' => ['BDT' => 120], 'time_last_update_unix' => now()->timestamp]),
            'shop.whois.com/*' => Http::response('Forbidden', 403),
        ]);
        $this->artisan('domains:sync-tld-prices')->assertFailed();
        $tld = Tld::firstOrFail();
        $this->assertSame('1800.00', $tld->renew_price);
        $this->assertSame('15.0000', $tld->renew_usd);
        $this->assertTrue($syncedAt->equalTo($tld->prices_synced_at));
        $this->assertSame('120', settings('usd_bdt_rate'));
        $this->assertStringContainsString('403', settings('tld_sync_error'));
    }

    public function test_stale_exchange_rates_leave_prices_and_previous_rate_unchanged(): void
    {
        Settings::set(['usd_bdt_rate' => 120], 'domains');
        Http::fake(['open.er-api.com/*' => Http::response(['result' => 'success', 'base_code' => 'USD', 'rates' => ['BDT' => 125], 'time_last_update_unix' => now()->subDays(7)->timestamp])]);
        try {
            app(TldPriceSyncService::class)->updateExchangeRate();
            $this->fail('Stale exchange rate was accepted.');
        } catch (RuntimeException) {
            $this->assertSame('120', settings('usd_bdt_rate'));
        }
    }

    public function test_invalid_payload_is_atomic(): void
    {
        $this->expectException(RuntimeException::class);
        try {
            app(TldPriceSyncService::class)->apply([
                'com' => ['register' => 10, 'renew' => 15, 'transfer' => 12],
                'org' => ['register' => 10, 'renew' => -1, 'transfer' => 12],
            ], 120);
        } finally {
            $this->assertSame(0, Tld::count());
        }
    }

    public function test_catalog_import_is_idempotent_and_keeps_existing_pricing(): void
    {
        Tld::create(['tld' => 'com', 'register_price' => 1600, 'is_active' => true]);
        $this->artisan('domains:import-tld-catalog')->assertSuccessful();
        $count = Tld::count();
        $this->assertGreaterThan(1000, $count);
        $this->artisan('domains:import-tld-catalog')->assertSuccessful();
        $this->assertSame($count, Tld::count());
        $this->assertSame('1600.00', Tld::where('tld', 'com')->firstOrFail()->register_price);
        $org = Tld::where('tld', 'org')->firstOrFail();
        $this->assertFalse($org->is_active);
        $this->assertTrue($org->auto_price_sync);
    }

    public function test_admin_pricing_form_can_be_constructed(): void
    {
        $schema = TldResource::form(Schema::make());
        $this->assertNotEmpty($schema->getComponents());
    }

    public function test_scheduled_sync_can_be_disabled(): void
    {
        Settings::set(['tld_auto_price_sync' => '0'], 'domains');
        Http::preventStrayRequests();
        $this->artisan('domains:sync-tld-prices --scheduled')->assertSuccessful();
        Http::assertNothingSent();
    }
}

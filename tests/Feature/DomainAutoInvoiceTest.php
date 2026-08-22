<?php

namespace Tests\Feature;

use App\Enums\DomainOrderStatus;
use App\Models\Domain;
use App\Models\DomainOrder;
use App\Models\Tld;
use App\Models\User;
use App\Services\InvoiceService;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DomainAutoInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeExpiringDomain(): Domain
    {
        Tld::create(['tld' => 'com', 'register_price' => 1600, 'renew_price' => 1600, 'transfer_price' => 1600, 'is_active' => true]);

        return Domain::create([
            'name' => 'expiring-client.com',
            'registrar' => 'resellcube',
            'user_id' => User::factory()->create()->id,
            'expires_at' => now()->addDays(20),
        ]);
    }

    public function test_disabled_by_default(): void
    {
        Mail::fake();
        $this->makeExpiringDomain();

        $this->artisan('billing:generate-domain-renewal-invoices')->assertSuccessful();

        $this->assertSame(0, DomainOrder::count());
    }

    public function test_generates_renewal_order_and_invoice_once(): void
    {
        Mail::fake();
        Settings::set(['domain_auto_invoice' => '1'], 'domains');

        $domain = $this->makeExpiringDomain();

        $this->artisan('billing:generate-domain-renewal-invoices')->assertSuccessful();

        $order = DomainOrder::firstOrFail();
        $this->assertSame('expiring-client.com', $order->domain_name);
        $this->assertSame(DomainOrderStatus::PendingPayment, $order->status);
        $this->assertSame('resellcube', $order->registrar);
        $this->assertSame($domain->user_id, $order->user_id);
        $this->assertNotNull(app(InvoiceService::class)->openInvoiceFor('domain_order', $order->id));

        // Second run: the open order blocks a duplicate.
        $this->artisan('billing:generate-domain-renewal-invoices')->assertSuccessful();
        $this->assertSame(1, DomainOrder::count());
    }

    public function test_unassigned_domains_are_ignored(): void
    {
        Mail::fake();
        Settings::set(['domain_auto_invoice' => '1'], 'domains');
        Tld::create(['tld' => 'com', 'register_price' => 1600, 'renew_price' => 1600, 'transfer_price' => 1600, 'is_active' => true]);

        Domain::create(['name' => 'company-own.com', 'expires_at' => now()->addDays(10)]);

        $this->artisan('billing:generate-domain-renewal-invoices')->assertSuccessful();

        $this->assertSame(0, DomainOrder::count());
    }
}

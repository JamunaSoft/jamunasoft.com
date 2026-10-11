<?php

namespace Tests\Feature;

use App\Enums\BillingCycle;
use App\Enums\ClientServiceStatus;
use App\Enums\DomainOrderStatus;
use App\Enums\DomainOrderType;
use App\Models\ClientService;
use App\Models\Domain;
use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Models\Tld;
use App\Models\User;
use App\Services\DomainOrderService;
use App\Services\InvoiceService;
use App\Services\RecurringBillingService;
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

    public function test_domain_renewal_appends_to_matching_service_invoice(): void
    {
        Mail::fake();
        Settings::set(['domain_auto_invoice' => '1'], 'domains');
        Settings::set(['invoice_ahead_days' => 31]);

        Tld::create(['tld' => 'com', 'register_price' => 1600, 'renew_price' => 1600, 'transfer_price' => 1600, 'is_active' => true]);
        $user = User::factory()->create();
        $dueAt = now()->addDays(20)->startOfDay();

        ClientService::create([
            'user_id' => $user->id,
            'name' => '2GB Hosting',
            'billing_cycle' => BillingCycle::Yearly,
            'price' => 2000,
            'status' => ClientServiceStatus::Active,
            'next_due_at' => $dueAt,
        ]);

        Domain::create([
            'name' => 'caringhandsbd.com',
            'registrar' => 'resellcube',
            'user_id' => $user->id,
            'expires_at' => $dueAt,
        ]);

        $this->assertCount(1, app(RecurringBillingService::class)->generateDueInvoices());
        $this->artisan('billing:generate-domain-renewal-invoices')->assertSuccessful();

        $this->assertSame(1, Invoice::count(), 'Domain renewal should join the existing service invoice.');

        $invoice = Invoice::firstOrFail()->refresh();
        $this->assertSame('3600.00', (string) $invoice->total);
        $this->assertCount(2, $invoice->items);
        $this->assertTrue($invoice->due_at->isSameDay($dueAt));
        $this->assertNotNull(app(InvoiceService::class)->openInvoiceFor('domain_order', DomainOrder::firstOrFail()->id));

        Settings::flush();
    }

    public function test_custom_price_generates_invoice_without_tld_pricing(): void
    {
        Mail::fake();
        Settings::set(['domain_auto_invoice' => '1'], 'domains');
        $user = User::factory()->create();
        Domain::create([
            'name' => 'custom.org',
            'user_id' => $user->id,
            'expires_at' => now()->addDays(11),
            'renewal_price' => 2500.50,
        ]);

        $this->artisan('billing:generate-domain-renewal-invoices')->assertSuccessful();
        $this->artisan('billing:generate-domain-renewal-invoices')->assertSuccessful();

        $this->assertSame(1, DomainOrder::count());
        $this->assertSame('2500.50', DomainOrder::firstOrFail()->amount);
        $this->assertSame('2500.50', Invoice::firstOrFail()->total);
    }

    public function test_custom_price_applies_to_owner_renewals_only_and_multiplies_by_years(): void
    {
        Mail::fake();
        $domain = $this->makeExpiringDomain();
        $domain->update(['renewal_price' => 1250]);
        $owner = $domain->user;
        $other = User::factory()->create();
        $service = app(DomainOrderService::class);

        $renewal = $service->create(
            customer: ['name' => $owner->name, 'email' => $owner->email, 'user_id' => $owner->id],
            domainName: $domain->name,
            type: DomainOrderType::Renew,
            years: 2,
        );
        $this->assertSame('2500.00', $renewal->amount);

        foreach ([DomainOrderType::Register, DomainOrderType::Transfer] as $type) {
            $order = $service->create(
                customer: ['name' => $owner->name, 'email' => $owner->email, 'user_id' => $owner->id],
                domainName: $domain->name,
                type: $type,
            );
            $this->assertSame('1600.00', $order->amount);
        }

        $order = $service->create(
            customer: ['name' => $other->name, 'email' => $other->email, 'user_id' => $other->id],
            domainName: $domain->name,
            type: DomainOrderType::Renew,
        );
        $this->assertSame('1600.00', $order->amount);

        $domain->update(['renewal_price' => null]);
        $this->assertSame(1600.0, $domain->renewalPrice());
        $domain->update(['renewal_price' => 0]);
        $this->assertSame(0.0, $domain->renewalPrice());
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

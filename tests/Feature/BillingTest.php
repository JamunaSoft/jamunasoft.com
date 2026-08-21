<?php

namespace Tests\Feature;

use App\Enums\BillingCycle;
use App\Enums\ClientServiceStatus;
use App\Enums\DomainOrderStatus;
use App\Enums\DomainOrderType;
use App\Enums\InvoiceStatus;
use App\Mail\InvoiceCreated;
use App\Mail\InvoicePaid;
use App\Mail\InvoiceReminder;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Models\Tld;
use App\Models\User;
use App\Services\DomainOrderService;
use App\Services\InvoiceService;
use App\Services\RecurringBillingService;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.spaceship.key' => 'test-key',
            'services.spaceship.secret' => 'test-secret',
            'services.spaceship.base_url' => 'https://spaceship.dev/api/v1',
        ]);
    }

    protected function fakeSpaceship(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();
            $method = $request->method();

            if (str_contains($url, '/available')) {
                return Http::response(['domain' => basename(dirname($url)), 'result' => 'available', 'premiumPricing' => []]);
            }

            if (str_contains($url, '/contacts')) {
                return Http::response(['contactId' => 'CONTACT123']);
            }

            if (str_contains($url, '/async-operations/')) {
                return Http::response(['status' => 'success']);
            }

            if ($method === 'POST' && preg_match('#/domains/[^/]+$#', $url)) {
                return Http::response([], 202, ['spaceship-async-operationid' => 'OP123']);
            }

            if ($method === 'GET' && preg_match('#/domains/[^/]+$#', $url)) {
                return Http::response([
                    'name' => basename($url),
                    'lifecycleStatus' => 'registered',
                    'registrationDate' => '2026-08-20T10:00:00Z',
                    'expirationDate' => '2027-08-20T10:00:00Z',
                ]);
            }

            return Http::response(['detail' => 'Unexpected request in test'], 500);
        });

        Settings::set([
            'domain_registrant_first_name' => 'Jamuna',
            'domain_registrant_last_name' => 'Soft',
            'domain_registrant_email' => 'domains@jamunasoft.com',
            'domain_registrant_phone' => '+880.1700000000',
            'domain_registrant_address' => 'Dhaka',
            'domain_registrant_city' => 'Dhaka',
            'domain_registrant_country' => 'BD',
        ], 'domains');
    }

    public function test_invoice_totals_and_full_payment(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $invoice = app(InvoiceService::class)->create(
            userId: $user->id,
            items: [
                ['description' => 'Website design', 'unit_price' => 15000],
                ['description' => 'Business email setup', 'quantity' => 2, 'unit_price' => 1000],
            ],
        );

        $this->assertSame('17000.00', (string) $invoice->total);
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->status);
        Mail::assertQueued(InvoiceCreated::class);

        app(InvoiceService::class)->recordPayment($invoice, 17000, 'bkash', 'TRX999');

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertCount(1, $invoice->payments);
        Mail::assertQueued(InvoicePaid::class);
    }

    public function test_partial_payment_keeps_invoice_unpaid(): void
    {
        Mail::fake();

        $invoice = app(InvoiceService::class)->create(
            userId: User::factory()->create()->id,
            items: [['description' => 'Hosting', 'unit_price' => 5000]],
        );

        app(InvoiceService::class)->recordPayment($invoice, 2000, 'cash');

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->status);
        $this->assertSame(3000.0, $invoice->balance());
        Mail::assertNotQueued(InvoicePaid::class);
    }

    public function test_domain_order_creates_invoice_and_paying_it_processes_the_order(): void
    {
        $this->fakeSpaceship();
        Mail::fake();

        Tld::create(['tld' => 'com', 'register_price' => 2000, 'renew_price' => 2200, 'transfer_price' => 2000, 'is_active' => true]);

        $user = User::factory()->create();

        $order = app(DomainOrderService::class)->create(
            customer: ['name' => $user->name, 'email' => $user->email, 'user_id' => $user->id],
            domainName: 'billingtest.com',
            type: DomainOrderType::Register,
        );

        $invoice = app(InvoiceService::class)->openInvoiceFor('domain_order', $order->id);
        $this->assertNotNull($invoice);
        $this->assertSame('2000.00', (string) $invoice->total);

        app(InvoiceService::class)->recordPayment($invoice, 2000, 'bkash', 'TRX1');

        $this->assertSame(DomainOrderStatus::Completed, $order->refresh()->status);
        $this->assertSame(InvoiceStatus::Paid, $invoice->refresh()->status);
        $this->assertCount(1, $invoice->payments);
    }

    public function test_confirming_order_payment_settles_the_linked_invoice(): void
    {
        $this->fakeSpaceship();
        Mail::fake();

        Tld::create(['tld' => 'com', 'register_price' => 2000, 'renew_price' => 2200, 'transfer_price' => 2000, 'is_active' => true]);

        $user = User::factory()->create();

        $order = app(DomainOrderService::class)->create(
            customer: ['name' => $user->name, 'email' => $user->email, 'user_id' => $user->id],
            domainName: 'billingtest2.com',
            type: DomainOrderType::Register,
        );

        app(DomainOrderService::class)->markPaid($order, 'bkash', 'TRX2');

        $invoice = Invoice::whereHas('items', fn ($q) => $q->where('item_type', 'domain_order')->where('item_id', $order->id))->first();

        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame(DomainOrderStatus::Completed, $order->refresh()->status);
    }

    public function test_recurring_invoices_are_generated_once_and_payment_advances_due_date(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $service = ClientService::create([
            'user_id' => $user->id,
            'name' => 'Web Hosting — Basic',
            'billing_cycle' => BillingCycle::Yearly,
            'price' => 4000,
            'status' => ClientServiceStatus::Active,
            'next_due_at' => now()->addDays(5),
        ]);

        $billing = app(RecurringBillingService::class);

        $this->assertCount(1, $billing->generateDueInvoices());
        $this->assertCount(0, $billing->generateDueInvoices(), 'An open invoice must not be duplicated.');

        $invoice = app(InvoiceService::class)->openInvoiceFor('client_service', $service->id);
        $this->assertSame('4000.00', (string) $invoice->total);

        $expectedNextDue = $service->next_due_at->copy()->addYearNoOverflow();

        app(InvoiceService::class)->recordPayment($invoice, 4000, 'bank');

        $service->refresh();
        $this->assertTrue($service->next_due_at->isSameDay($expectedNextDue));
        $this->assertSame(ClientServiceStatus::Active, $service->status);
    }

    public function test_invoice_reminders_are_throttled(): void
    {
        Mail::fake();

        $invoice = app(InvoiceService::class)->create(
            userId: User::factory()->create()->id,
            items: [['description' => 'Hosting', 'unit_price' => 5000]],
            dueAt: now()->addDay(),
            sendEmail: false,
        );

        $billing = app(RecurringBillingService::class);

        $this->assertSame(1, $billing->sendReminders());
        Mail::assertQueued(InvoiceReminder::class, 1);

        $this->assertSame(0, $billing->sendReminders(), 'Reminders must not repeat within the interval.');
        $this->assertNotNull($invoice->refresh()->last_reminded_at);
    }

    public function test_clients_see_only_their_own_invoices(): void
    {
        $mine = User::factory()->create();
        $other = User::factory()->create();

        $myInvoice = app(InvoiceService::class)->create($mine->id, [['description' => 'My hosting', 'unit_price' => 100]], sendEmail: false);
        $otherInvoice = app(InvoiceService::class)->create($other->id, [['description' => 'Other hosting', 'unit_price' => 100]], sendEmail: false);

        $this->actingAs($mine)
            ->get('/client/invoices')
            ->assertOk()
            ->assertSee($myInvoice->reference)
            ->assertDontSee($otherInvoice->reference);
    }
}

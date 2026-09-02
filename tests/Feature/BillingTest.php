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

    public function test_invoice_emails_also_go_to_the_secondary_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $user->update(['secondary_email' => 'accounts@client-company.com']);

        app(InvoiceService::class)->create(
            userId: $user->id,
            items: [['description' => 'Hosting', 'unit_price' => 5000]],
        );

        Mail::assertQueued(InvoiceCreated::class, fn ($mail) => $mail->hasTo($user->email)
            && $mail->hasTo('accounts@client-company.com'));
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

    public function test_due_services_of_one_client_are_consolidated_into_one_invoice(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $makeService = fn (User $owner, string $name, float $price, int $dueInDays) => ClientService::create([
            'user_id' => $owner->id,
            'name' => $name,
            'billing_cycle' => BillingCycle::Monthly,
            'price' => $price,
            'status' => ClientServiceStatus::Active,
            'next_due_at' => now()->addDays($dueInDays),
        ]);

        $maintenance = $makeService($user, 'Website Maintenance', 2500, 5);
        $hosting = $makeService($user, 'Web Hosting (VPS - BDIX)', 3500, 3);
        $boosting = $makeService($user, 'FB Boosting (30,000 +-)', 30000, 5);
        $otherService = $makeService($otherUser, 'Other Client Hosting', 1000, 4);

        $invoices = app(RecurringBillingService::class)->generateDueInvoices();

        // One consolidated invoice for the first client, one for the other.
        $this->assertCount(2, $invoices);

        $invoice = app(InvoiceService::class)->openInvoiceFor('client_service', $maintenance->id);
        $this->assertCount(3, $invoice->items);
        $this->assertSame('36000.00', (string) $invoice->total);
        $this->assertTrue($invoice->due_at->isSameDay($hosting->next_due_at), 'Due date follows the earliest service.');
        $this->assertNotSame($invoice->id, app(InvoiceService::class)->openInvoiceFor('client_service', $otherService->id)->id);

        // Idempotent: nothing new while the invoice stays open.
        $this->assertCount(0, app(RecurringBillingService::class)->generateDueInvoices());

        // Paying the one invoice advances every bundled service.
        $expected = [
            $maintenance->id => $maintenance->next_due_at->copy()->addMonthNoOverflow(),
            $hosting->id => $hosting->next_due_at->copy()->addMonthNoOverflow(),
            $boosting->id => $boosting->next_due_at->copy()->addMonthNoOverflow(),
        ];

        app(InvoiceService::class)->recordPayment($invoice, 36000, 'bank');

        foreach ($expected as $id => $date) {
            $this->assertTrue(ClientService::find($id)->next_due_at->isSameDay($date));
        }
    }

    public function test_invoice_lead_time_is_configurable(): void
    {
        Mail::fake();

        ClientService::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Hosting',
            'billing_cycle' => BillingCycle::Monthly,
            'price' => 3500,
            'status' => ClientServiceStatus::Active,
            'next_due_at' => now()->addDays(12),
        ]);

        // Outside the default 7-day window: nothing yet.
        $this->assertCount(0, app(RecurringBillingService::class)->generateDueInvoices());

        // With a 14-day lead time the invoice goes out today.
        Settings::set(['invoice_ahead_days' => 14]);
        $this->assertCount(1, app(RecurringBillingService::class)->generateDueInvoices());

        Settings::flush();
    }

    public function test_monthly_billing_day_batches_the_whole_month(): void
    {
        Mail::fake();

        $makeService = fn (int $dueInDays) => ClientService::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Hosting',
            'billing_cycle' => BillingCycle::Monthly,
            'price' => 3500,
            'status' => ClientServiceStatus::Active,
            'next_due_at' => now()->addDays($dueInDays),
        ]);

        $makeService(20); // outside the 7-day lead window

        // Today is not the batch day: nothing happens.
        Settings::set(['invoice_generation_day' => now()->addDays(2)->day]);
        $this->assertCount(0, app(RecurringBillingService::class)->generateDueInvoices());

        // Today IS the batch day: everything due within 31 days is billed.
        Settings::set(['invoice_generation_day' => now()->day]);
        $this->assertCount(1, app(RecurringBillingService::class)->generateDueInvoices());

        // Safety net: a service added mid-month, due in 3 days, is still
        // billed on a non-batch day via the lead-time window.
        Settings::set(['invoice_generation_day' => now()->addDays(2)->day]);
        $makeService(3);
        $this->assertCount(1, app(RecurringBillingService::class)->generateDueInvoices());

        Settings::flush();
    }

    public function test_invoice_all_services_bills_everything_now_without_emailing(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        foreach ([['Maintenance', 2500], ['Hosting', 3500]] as [$name, $price]) {
            ClientService::create([
                'user_id' => $user->id,
                'name' => $name,
                'description' => $name === 'Hosting' ? "Machine Type: KVM\nMemory up to 4GB" : null,
                'domain' => $name === 'Hosting' ? 'example.com' : null,
                'billing_cycle' => BillingCycle::Monthly,
                'price' => $price,
                'status' => ClientServiceStatus::Active,
                // Far outside the 7-day auto window — "bill now" ignores it.
                'next_due_at' => now()->addDays(30),
            ]);
        }

        $invoices = app(RecurringBillingService::class)->invoiceAllServicesFor($user);

        $this->assertCount(1, $invoices, 'Same billing profile — one consolidated invoice.');
        $invoice = $invoices->first();
        $this->assertCount(2, $invoice->items);
        $this->assertSame('6000.00', (string) $invoice->total);
        Mail::assertNothingQueued();

        // Specs, domain and the covered billing period all land in the line description.
        $hostingItem = $invoice->items->firstWhere('title', 'Hosting — Monthly');
        $expectedStart = now()->addDays(30);
        $this->assertStringContainsString('Machine Type: KVM', $hostingItem->description);
        $this->assertStringContainsString('Domain: example.com', $hostingItem->description);
        $this->assertStringContainsString(
            sprintf('Duration: %s – %s', $expectedStart->format('M d, Y'), $expectedStart->copy()->addMonthNoOverflow()->subDay()->format('M d, Y')),
            $hostingItem->description,
        );

        $this->assertCount(
            0,
            app(RecurringBillingService::class)->invoiceAllServicesFor($user),
            'Services already on an open invoice must not be billed again.',
        );
    }

    public function test_consolidated_services_keep_billing_together_every_cycle(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        foreach ([['Hosting', 2000, 5], ['Maintenance', 2500, 15]] as [$name, $price, $dueIn]) {
            ClientService::create([
                'user_id' => $user->id,
                'name' => $name,
                'billing_cycle' => BillingCycle::Monthly,
                'price' => $price,
                'status' => ClientServiceStatus::Active,
                'next_due_at' => now()->addDays($dueIn),
            ]);
        }

        // Batch day: both services bill on ONE invoice despite different due dates.
        Settings::set(['invoice_generation_day' => now()->day]);

        $first = collect(app(RecurringBillingService::class)->generateDueInvoices());
        $this->assertCount(1, $first);
        $this->assertCount(2, $first->first()->items);
        $this->assertSame('4500.00', (string) $first->first()->total);

        // Paying advances BOTH services a month…
        app(InvoiceService::class)->recordPayment($first->first(), 4500, 'bkash');

        // …and the next cycle bills them together again.
        $this->travel(1)->months();
        Settings::set(['invoice_generation_day' => now()->day]);

        $second = collect(app(RecurringBillingService::class)->generateDueInvoices());
        $this->assertCount(1, $second, 'The consolidated bill recurs as one invoice every cycle.');
        $this->assertCount(2, $second->first()->items);

        $this->travelBack();
        Settings::flush();
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

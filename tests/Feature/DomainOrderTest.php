<?php

namespace Tests\Feature;

use App\Enums\DomainOrderStatus;
use App\Enums\DomainOrderType;
use App\Jobs\PollDomainTransfer;
use App\Mail\DomainOrderCompleted;
use App\Mail\DomainOrderConfirmation;
use App\Mail\DomainTransferStarted;
use App\Models\Domain;
use App\Models\DomainOrder;
use App\Models\Tld;
use App\Models\User;
use App\Services\DomainOrderService;
use App\Services\Registrars\RegistrarException;
use App\Services\Registrars\SpaceshipRegistrar;
use App\Services\Spaceship\SpaceshipClient;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DomainOrderTest extends TestCase
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

        Tld::create(['tld' => 'com', 'register_price' => 2000, 'renew_price' => 2200, 'transfer_price' => 2000, 'is_active' => true]);
    }

    protected function fillRegistrantSettings(): void
    {
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

            if (str_contains($url, '/nameservers')) {
                return Http::response([]);
            }

            if ($method === 'POST' && preg_match('#/domains/[^/]+$#', $url)) {
                return Http::response([], 202, ['spaceship-async-operationid' => 'OP123']);
            }

            if ($method === 'GET' && preg_match('#/domains/[^/]+$#', $url)) {
                return Http::response([
                    'name' => basename($url),
                    'lifecycleStatus' => 'registered',
                    'verificationStatus' => 'success',
                    'autoRenew' => false,
                    'registrationDate' => '2026-08-20T10:00:00Z',
                    'expirationDate' => '2027-08-20T10:00:00Z',
                    'privacyProtection' => ['level' => 'high'],
                    'nameservers' => ['provider' => 'basic', 'hosts' => []],
                ]);
            }

            return Http::response(['detail' => 'Unexpected request in test: '.$method.' '.$url], 500);
        });
    }

    public function test_domains_page_loads(): void
    {
        $this->get('/domains')->assertOk()->assertSee('.com');
    }

    public function test_search_shows_available_domain_with_price(): void
    {
        $this->fakeSpaceship();

        $this->get('/domains?q=mytestshop')
            ->assertOk()
            ->assertSee('mytestshop.com')
            ->assertSee('2,000');
    }

    public function test_order_is_created_in_pending_payment(): void
    {
        $this->fakeSpaceship();
        Mail::fake();

        $response = $this->post('/domains/order', [
            'name' => 'Rahim Uddin',
            'email' => 'rahim@example.com',
            'phone' => '01700000000',
            'domain' => 'mytestshop.com',
            'years' => 2,
        ]);

        $order = DomainOrder::firstOrFail();

        $response->assertRedirect(route('domains.order.status', $order->reference));

        $this->assertSame(DomainOrderStatus::PendingPayment, $order->status);
        $this->assertSame('mytestshop.com', $order->domain_name);
        $this->assertSame('4000.00', (string) $order->amount);

        Mail::assertQueued(DomainOrderConfirmation::class);

        $this->get(route('domains.order.status', $order->reference))
            ->assertOk()
            ->assertSee($order->reference);
    }

    public function test_payment_confirmation_registers_the_domain(): void
    {
        $this->fakeSpaceship();
        $this->fillRegistrantSettings();
        Mail::fake();

        $user = User::factory()->create();

        $service = app(DomainOrderService::class);

        $order = $service->create(
            customer: ['name' => $user->name, 'email' => $user->email, 'user_id' => $user->id],
            domainName: 'mytestshop.com',
            type: DomainOrderType::Register,
        );

        $service->markPaid($order, 'bkash', 'TRX123');

        $order->refresh();

        $this->assertSame(DomainOrderStatus::Completed, $order->status);
        $this->assertSame('OP123', $order->spaceship_operation_id);

        $domain = Domain::where('name', 'mytestshop.com')->firstOrFail();
        $this->assertSame('registered', $domain->lifecycle_status);
        $this->assertSame($user->id, $domain->user_id);
        $this->assertSame(['cl1.jamunasoft.com', 'cl2.jamunasoft.com'], $domain->nameservers);

        Http::assertSent(fn (Request $request) => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/domains/mytestshop.com/nameservers'));

        Mail::assertQueued(DomainOrderCompleted::class);

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/domains/mytestshop.com')
            && $request['contacts']['registrant'] === 'CONTACT123');
    }

    public function test_payment_confirmation_starts_resellcube_to_spaceship_transfer(): void
    {
        Mail::fake();
        config([
            'services.resellcube.user_id' => 'reseller',
            'services.resellcube.api_key' => 'secret',
            'services.resellcube.base_url' => 'https://resellcube.test/api',
        ]);

        $user = User::factory()->create();
        Domain::create([
            'name' => 'mytestshop.com',
            'registrar' => 'resellcube',
            'user_id' => $user->id,
            'meta' => [
                'domsecret' => 'SOURCE-EPP-123',
                'registrantcontact' => [
                    'name' => 'Domain Owner', 'emailaddr' => 'owner@example.com',
                    'address1' => '12 Example Road', 'city' => 'Dhaka',
                    'zip' => '1229', 'country' => 'BD',
                    'telnocc' => '880', 'telno' => '1712345678',
                ],
            ],
        ]);

        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'resellcube.test')) {
                if (str_contains($request->url(), '/orderid.json')) {
                    return Http::response('456');
                }

                return Http::response([]);
            }

            if (str_contains($request->url(), '/contacts')) {
                return Http::response(['contactId' => 'CONTACT123']);
            }

            if (str_contains($request->url(), '/async-operations/')) {
                return Http::response(['status' => 'success']);
            }

            if (str_ends_with($request->url(), '/domains/mytestshop.com/transfer') && $request->method() === 'GET') {
                return Http::response(['direction' => 'in', 'status' => 'completed', 'finishedAt' => '2026-08-20T10:00:00Z']);
            }

            if (str_ends_with($request->url(), '/domains/mytestshop.com/transfer')) {
                return Http::response([], 202, ['spaceship-async-operationid' => 'TRANSFER123']);
            }

            if (str_ends_with($request->url(), '/domains/mytestshop.com')) {
                return Http::response([
                    'name' => 'mytestshop.com',
                    'lifecycleStatus' => 'registered',
                    'verificationStatus' => 'success',
                    'expirationDate' => '2027-08-20T10:00:00Z',
                    'nameservers' => ['provider' => 'basic', 'hosts' => []],
                ]);
            }

            return Http::response(['detail' => 'Unexpected request: '.$request->method().' '.$request->url()], 500);
        });

        $order = app(DomainOrderService::class)->create(
            customer: ['name' => $user->name, 'email' => $user->email, 'user_id' => $user->id],
            domainName: 'mytestshop.com',
            type: DomainOrderType::Transfer,
        );

        app(DomainOrderService::class)->markPaid($order, 'bkash', 'TRX-TRANSFER');

        $order->refresh();

        $this->assertSame(DomainOrderStatus::Completed, $order->status);
        $this->assertSame('spaceship', $order->registrar);
        $this->assertSame('TRANSFER123', $order->spaceship_operation_id);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/contacts')
            && $request['firstName'] === 'Domain'
            && $request['lastName'] === 'Owner'
            && $request['email'] === 'owner@example.com'
            && $request['phone'] === '+880.1712345678');
        Http::assertSent(fn (Request $request) => $request->method() === 'POST' && str_ends_with($request->url(), '/domains/mytestshop.com/transfer')
            && $request['authCode'] === 'SOURCE-EPP-123'
            && $request['autoRenew'] === false
            && $request['contacts']['registrant'] === 'CONTACT123'
            && $request['privacyProtection'] === ['level' => 'high', 'userConsent' => true]
            && ! array_key_exists('years', $request->data()));
    }

    public function test_transfer_with_missing_owner_contact_does_not_use_default_contact(): void
    {
        $this->fillRegistrantSettings();
        Http::fake();
        Domain::create(['name' => 'missing-owner.com', 'registrar' => 'resellcube']);

        try {
            app(SpaceshipRegistrar::class)->transfer('missing-owner.com', 'EPP', 1);
            $this->fail('Expected missing owner contact to stop the transfer.');
        } catch (RegistrarException $e) {
            $this->assertStringContainsString('Domain owner contact', $e->getMessage());
            Http::assertNothingSent();
        }
    }

    public function test_accepted_transfer_stays_processing_and_sends_started_email_once(): void
    {
        Mail::fake();
        Http::fake([
            '*/async-operations/*' => Http::response(['status' => 'success']),
            '*/transfer' => Http::response(['direction' => 'in', 'status' => 'pending', 'finishedAt' => null]),
        ]);
        $order = $this->processingTransfer();
        $job = new PollDomainTransfer($order);
        $job->handle(app(DomainOrderService::class), app(SpaceshipClient::class));
        $job->handle(app(DomainOrderService::class), app(SpaceshipClient::class));
        $this->assertSame(DomainOrderStatus::Processing, $order->fresh()->status);
        Mail::assertQueued(DomainTransferStarted::class, 1);
        Mail::assertNotQueued(DomainOrderCompleted::class);
        Http::assertNotSent(fn (Request $r) => $r->method() === 'POST');
    }

    public function test_transfer_lookup_failure_and_exhaustion_do_not_enable_retry(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response(['detail' => 'Unavailable'], 403)]);
        $order = $this->processingTransfer();
        $job = new PollDomainTransfer($order);
        $job->handle(app(DomainOrderService::class), app(SpaceshipClient::class));
        $this->assertSame(DomainOrderStatus::Processing, $order->fresh()->status);
        $job->failed(new \RuntimeException('Attempts exhausted'));
        $this->assertSame(DomainOrderStatus::Processing, $order->fresh()->status);
        Mail::assertNothingQueued();
    }

    public function test_registrar_rejected_transfer_is_failed_without_completion_email(): void
    {
        Mail::fake();
        Http::fake([
            '*/async-operations/*' => Http::response(['status' => 'success']),
            '*/transfer' => Http::response(['direction' => 'in', 'status' => 'failed', 'finishedAt' => now()->toIso8601String()]),
        ]);
        $order = $this->processingTransfer();
        (new PollDomainTransfer($order))->handle(app(DomainOrderService::class), app(SpaceshipClient::class));
        $this->assertSame(DomainOrderStatus::Failed, $order->fresh()->status);
        Mail::assertNothingQueued();
    }

    public function test_completion_wording_depends_on_transfer_source(): void
    {
        $order = $this->processingTransfer();
        $order->meta = ['source_registrar' => 'resellcube'];
        $this->assertStringContainsString('The renewal for', (new DomainOrderCompleted($order))->render());
        $order->meta = ['source_registrar' => 'other'];
        $html = (new DomainOrderCompleted($order))->render();
        $this->assertStringContainsString('transfer has been completed', $html);
        $this->assertStringNotContainsString('The renewal for', $html);
    }

    private function processingTransfer(): DomainOrder
    {
        return DomainOrder::create([
            'reference' => DomainOrder::generateReference(),
            'domain_name' => 'pending-transfer.com', 'registrar' => 'spaceship',
            'customer_name' => 'Domain Owner', 'customer_email' => 'owner@example.com',
            'type' => DomainOrderType::Transfer, 'years' => 1, 'amount' => 1600,
            'status' => DomainOrderStatus::Processing, 'spaceship_operation_id' => 'TRANSFER123',
        ]);
    }

    public function test_taken_domain_cannot_be_ordered(): void
    {
        Http::fake(['*/available*' => Http::response(['domain' => 'google.com', 'result' => 'taken', 'premiumPricing' => []])]);

        $this->from('/domains')->post('/domains/order', [
            'name' => 'Rahim Uddin',
            'email' => 'rahim@example.com',
            'domain' => 'google.com',
            'years' => 1,
        ])->assertRedirect('/domains')->assertSessionHasErrors('domain');

        $this->assertSame(0, DomainOrder::count());
    }

    public function test_unsupported_tld_is_rejected(): void
    {
        $this->from('/domains')->post('/domains/order', [
            'name' => 'Rahim Uddin',
            'email' => 'rahim@example.com',
            'domain' => 'example.xyz',
            'years' => 1,
        ])->assertRedirect('/domains')->assertSessionHasErrors('domain');

        $this->assertSame(0, DomainOrder::count());
    }

    public function test_honeypot_submissions_are_ignored(): void
    {
        $this->post('/domains/order', [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'domain' => 'spammy.com',
            'years' => 1,
            'website_url_hp' => 'https://spam.example',
        ])->assertRedirect(route('domains.index'));

        $this->assertSame(0, DomainOrder::count());
    }
}

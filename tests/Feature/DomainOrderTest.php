<?php

namespace Tests\Feature;

use App\Enums\DomainOrderStatus;
use App\Enums\DomainOrderType;
use App\Mail\DomainOrderCompleted;
use App\Mail\DomainOrderConfirmation;
use App\Models\Domain;
use App\Models\DomainOrder;
use App\Models\Tld;
use App\Models\User;
use App\Services\DomainOrderService;
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
                return Http::response(['isAvailable' => true, 'isPremium' => false]);
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

        Mail::assertQueued(DomainOrderCompleted::class);

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/domains/mytestshop.com')
            && $request['contacts']['registrant'] === 'CONTACT123');
    }

    public function test_taken_domain_cannot_be_ordered(): void
    {
        Http::fake(['*/available*' => Http::response(['isAvailable' => false])]);

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

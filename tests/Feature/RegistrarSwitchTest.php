<?php

namespace Tests\Feature;

use App\Enums\DomainOrderStatus;
use App\Enums\DomainOrderType;
use App\Models\Domain;
use App\Models\Tld;
use App\Models\User;
use App\Services\DomainOrderService;
use App\Services\DomainSearchService;
use App\Services\Registrars\RegistrarManager;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrarSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.spaceship.key' => 'test-key',
            'services.spaceship.secret' => 'test-secret',
            'services.spaceship.base_url' => 'https://spaceship.dev/api/v1',
            'services.resellcube.user_id' => '12345',
            'services.resellcube.api_key' => 'rc-test-key',
            'services.resellcube.base_url' => 'https://manage.resellcube.com/api',
        ]);

        Tld::create(['tld' => 'com', 'register_price' => 1600, 'renew_price' => 1600, 'transfer_price' => 1600, 'is_active' => true]);

        Settings::set([
            'domain_registrant_first_name' => 'Jamuna',
            'domain_registrant_last_name' => 'Soft',
            'domain_registrant_email' => 'domains@jamunasoft.com',
            'domain_registrant_phone' => '+880.1700000000',
            'domain_registrant_address' => 'Dhaka',
            'domain_registrant_city' => 'Dhaka',
            'domain_registrant_country' => 'BD',
            'domain_default_nameservers' => "ns1.jamunasoft.com\nns2.jamunasoft.com",
        ], 'domains');
    }

    protected function fakeResellCube(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();

            if (str_contains($url, 'manage.resellcube.com')) {
                return match (true) {
                    str_contains($url, '/domains/available.json') => Http::response([
                        'rctest.com' => ['status' => 'available', 'classkey' => 'domcno'],
                    ]),
                    str_contains($url, '/customers/v2/signup.json') => Http::response('4321'),
                    str_contains($url, '/contacts/add.json') => Http::response('8765'),
                    str_contains($url, '/domains/register.json') => Http::response([
                        'entityid' => 99001, 'actionstatus' => 'Success', 'description' => 'rctest.com',
                    ]),
                    str_contains($url, '/domains/orderid.json') => Http::response('99001'),
                    str_contains($url, '/domains/details.json') => Http::response([
                        'domainname' => 'rctest.com',
                        'currentstatus' => 'Active',
                        'creationtime' => (string) now()->timestamp,
                        'endtime' => (string) now()->addYear()->timestamp,
                        'ns1' => 'ns1.jamunasoft.com',
                        'ns2' => 'ns2.jamunasoft.com',
                    ]),
                    str_contains($url, '/domains/renew.json') => Http::response([
                        'entityid' => 99002, 'actionstatus' => 'Success',
                    ]),
                    default => Http::response(['status' => 'ERROR', 'message' => 'Unexpected RC request: '.$url], 500),
                };
            }

            return Http::response(['detail' => 'Unexpected request: '.$url], 500);
        });
    }

    public function test_search_uses_the_selected_registrar(): void
    {
        Settings::set(['active_domain_registrar' => 'resellcube'], 'domains');
        $this->fakeResellCube();

        ['results' => $results] = app(DomainSearchService::class)->search('rctest.com');

        $this->assertTrue($results[0]['available']);
        $this->assertSame(1600.0, $results[0]['price']);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'manage.resellcube.com/api/domains/available.json'));
    }

    public function test_registration_goes_through_resellcube_when_selected(): void
    {
        Settings::set(['active_domain_registrar' => 'resellcube'], 'domains');
        $this->fakeResellCube();
        Mail::fake();

        $user = User::factory()->create();
        $service = app(DomainOrderService::class);

        $order = $service->create(
            customer: ['name' => $user->name, 'email' => $user->email, 'user_id' => $user->id],
            domainName: 'rctest.com',
            type: DomainOrderType::Register,
        );

        $this->assertSame('resellcube', $order->registrar);

        $service->markPaid($order, 'bkash', 'TRX-RC1');

        $order->refresh();
        $this->assertSame(DomainOrderStatus::Completed, $order->status);

        $domain = Domain::where('name', 'rctest.com')->firstOrFail();
        $this->assertSame('resellcube', $domain->registrar);
        $this->assertSame('active', $domain->lifecycle_status);
        $this->assertSame($user->id, $domain->user_id);
        $this->assertNotNull($domain->expires_at);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/domains/register.json')
            && str_contains($request->url(), 'invoice-option=NoInvoice'));
    }

    public function test_renewal_uses_the_domains_own_registrar_not_the_active_setting(): void
    {
        // Active provider is ResellCube, but this domain lives at Spaceship.
        Settings::set(['active_domain_registrar' => 'resellcube'], 'domains');
        Mail::fake();

        $user = User::factory()->create();

        Domain::create([
            'name' => 'spaceshipdomain.com',
            'registrar' => 'spaceship',
            'user_id' => $user->id,
            'expires_at' => now()->addMonths(2),
        ]);

        $order = app(DomainOrderService::class)->create(
            customer: ['name' => $user->name, 'email' => $user->email, 'user_id' => $user->id],
            domainName: 'spaceshipdomain.com',
            type: DomainOrderType::Renew,
        );

        $this->assertSame('spaceship', $order->registrar);
    }

    public function test_resellcube_sync_pulls_all_account_domains(): void
    {
        // A Spaceship domain must never be flagged missing by the RC sync.
        Domain::create(['name' => 'spaceshipdomain.com', 'registrar' => 'spaceship']);

        $orderIds = ['rcone.com' => 501, 'rctwo.com' => 502];

        Http::fake(function (Request $request) use ($orderIds) {
            $url = $request->url();
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            return match (true) {
                str_contains($url, '/domains/search.json') => Http::response([
                    'recsindb' => '2',
                    'recsonpage' => '2',
                    '1' => ['entity.description' => 'rcone.com', 'orders.orderid' => '501'],
                    '2' => ['entity.description' => 'rctwo.com', 'orders.orderid' => '502'],
                ]),
                str_contains($url, '/domains/orderid.json') => Http::response(
                    (string) ($orderIds[$query['domain-name']] ?? 0)
                ),
                str_contains($url, '/domains/details.json') => Http::response([
                    'domainname' => array_search((int) $query['order-id'], $orderIds, true),
                    'currentstatus' => 'Active',
                    'creationtime' => (string) now()->subYear()->timestamp,
                    'endtime' => (string) now()->addMonths(7)->timestamp,
                    'ns1' => 'ns1.jamunasoft.com',
                    'ns2' => 'ns2.jamunasoft.com',
                ]),
                default => Http::response(['status' => 'ERROR', 'message' => 'Unexpected: '.$url], 500),
            };
        });

        $result = app(RegistrarManager::class)->for('resellcube')->syncAll();

        $this->assertSame(2, $result['synced']);
        $this->assertSame(2, $result['created']);
        $this->assertSame([], $result['missing']);

        $this->assertSame('resellcube', Domain::where('name', 'rcone.com')->value('registrar'));
        $this->assertSame('resellcube', Domain::where('name', 'rctwo.com')->value('registrar'));
        $this->assertNotNull(Domain::where('name', 'rctwo.com')->first()->expires_at);
    }

    public function test_manager_falls_back_to_spaceship(): void
    {
        $manager = app(RegistrarManager::class);

        $this->assertSame('spaceship', $manager->activeKey());
        $this->assertSame('spaceship', $manager->for(null)->key());
        $this->assertSame('resellcube', $manager->for('resellcube')->key());

        Settings::set(['active_domain_registrar' => 'nonsense'], 'domains');
        $this->assertSame('spaceship', $manager->activeKey());
    }
}
